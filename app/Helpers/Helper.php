<?php

use App\Models\CFG\CFG_coverages;
use App\Models\CFG\CFG_deductibles;
use App\Models\DeductibleQLI;
use App\Models\CFG\CFG_rates_rc_options;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Relationship;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Calculation\Logical\Boolean;
use App\Services\QUO\QUOQuotationsService;

if (!function_exists('slug_gerar')) {
    function slug_gerar()
    {
        $slug = Str::random(7) . uniqid(date('HisYmd')) . Str::random(7);
        return $slug;
    }
}

function getGroupIdOfCoverage($coverage_id)
{
    $coverage = Coverage::find($coverage_id);
    return $coverage?->group_id; // Safe navigation para evitar erro se for null
}



if (!function_exists('converterDataEmAno')) {
    function converterDataEmAno($data_nascimento)
    {
        // Converte a data de nascimento para um objeto DateTime
        $dataNasc = new DateTime($data_nascimento);

        // Obtém a data atual
        $dataAtual = new DateTime();

        // Calcula a diferença entre as datas
        $diferenca = $dataAtual->diff($dataNasc);

        // Obtém a idade em anos
        $idade = $diferenca->y;

        // Opcional: exibir ou usar a idade
        // Por exemplo, você pode retornar ou armazenar a idade
        return $idade;
    }
}
function selectedCoverage($qli, $coverage_id = null)
{
    // dd($qli);
    // if ($coverage['rate_type'] == "ZERO") {
    foreach ($qli as $q) {
        // if ($q['coverage_id'] == $coverage['id']) {
        if (isset($q['coverage_id']) && $q['coverage_id'] == $coverage_id) {
            return $q['status'];

            // }
        }
    }
    return false;

    // }
    // return true;
}
function findQLI($qli, $coverage)
{
    // if ($coverage['rate_type'] == "ZERO") {
    // dd($qli);

    $filter = array_filter($qli, fn($item) => $item['coverage_id'] === $coverage['id']);
    $filter = array_shift($filter);
    return $filter;
    // return [200,400,3000];
}
// function findQLI($qli, $coverage)
// {
//     return [200, 400, 3000];
// }
/**
 * Verifica o valor do prêmio com base no tipo e objeto
 *
 * @param array $object
 * @param string $type
 * @return mixed
 */
function checkPremium($object, $coverage, $QLI, $anyAnnex = null)
{
    // dd($object, $coverage, $QLI, $anyAnnex ); 
    // Encontra o item correspondente no array QLI
    $q = array_filter($QLI, function ($item) use ($object, $coverage) {
        $object_id = isset($item['object_id']) ? $item['object_id'] : $item['insured_object_id'];
        return $item['coverage_id'] === $coverage['id'] && $object_id === $object['id'];
    });

    // Pega o primeiro elemento encontrado (se existir)
    $q = reset($q);

    // Verifica se o item foi encontrado
    if (!$q) {
        return 0;
    }

    // Lógica CORRIGIDA para RC FACULTAT.
    if ($coverage['short_name'] == 'RC FACULTAT.') {

        // Retorna o valor correto do rc_fac
        return isset($q['ratesObject'][0]['rc_fac']) ? $q['ratesObject'][0]['rc_fac'] : 0;
    }

    // Para outras coberturas, retorna o prémio normal
    $value = arredondarBase5(isset($q['premiunComercial']) ? $q['premiunComercial'] : ($q['coverage_premium'] ?? 0));
    // if ($q['coverageObject']['name'] == "DANOS PRÓPRIOS") {
    //     dd($value,$q['premiunComercial']);
    //     // premiunComercial
    // }
    if (!$anyAnnex) {

        $q = array_filter($QLI, function ($item) use ($object) {
            $object_id = $item['object_id'] ?? $item['insured_object_id'] ?? null;
            return isset($item['coverageObject']['short_name'])
                && $item['coverageObject']['short_name'] === 'RC FACULTAT.'
                && $object_id === $object['id'] && $item['status'];
        });
        // dd($q);
        $q = array_shift($q);
        if ($q) {

            $rate = CFG_rates_rc_options::find($q['rate_id']);
            $value += $value * ($rate->perc_rate / 100);
        }
    }
    return $value;
}



if (!function_exists('get_annexes')) {
    /**
     * Retorna os anexos únicos e ordenados em ordem crescente.
     *
     * @param array $surcharges
     * @return array
     */
    function get_annexes(array $surcharges): array
    {
        $annexes = array_column($surcharges, 'annex'); // pega só os anexos
        $annexes = array_unique($annexes);             // remove repetidos
        sort($annexes, SORT_STRING);                   // ordena em ordem crescente (alfabética)
        return $annexes;
    }
}

// function groupAdjustmentsByQli(array $adjustments): array
// {
//     $result = [];
//     $letters = range('B', 'Z');
//     $letterIndex = 0;
//     $adjustments = collect($adjustments)->where("type", "surcharge")->toArray();
//     foreach ($adjustments as $key => $adj) {

//         $qli = $adj['qli_id'];

//         // Procurar o primeiro anexo disponível onde este qli_id ainda não existe
//         $placed = false;
//         foreach ($result as $letter => $items) {
//             $qlisInAnnex = array_column($items, 'qli_id');
//             if (!in_array($qli, $qlisInAnnex)) {
//                 $result[$letter][$key] = $adj;
//                 $placed = true;
//                 break;
//             }
//         }

//         // Se não encontrou anexo disponível, cria um novo
//         if (!$placed) {
//             $currentLetter = $letters[$letterIndex] ?? ('Z' . $letterIndex);
//             $result[$currentLetter][$key] = $adj;
//             $letterIndex++;
//         }
//     }

//     return $result;
// }



function groupAdjustmentsByQli(array $adjustments, $annex = 'A'): array
{
    $result = [];

    if ($annex !== 'A') {
        // Se annex for diferente de 'A', agrupa tudo sob essa letra
        foreach ($adjustments as $adj) {
            $result[$annex][] = $adj;
        }
    } else {
        // Se annex for 'A', usa a lógica original de agrupamento por rate
        $letters = range('B', 'Z');
        $letterIndex = 0;
        $usedGroups = [];

        foreach ($adjustments as $adj) {
            // Agrupa por rate (taxa)
            $groupKey = $adj['rate'];

            // Se já existe uma letra para esta rate, usa a mesma letra
            if (isset($usedGroups[$groupKey])) {
                $currentLetter = $usedGroups[$groupKey];
                $result[$currentLetter][] = $adj; // Adiciona ao grupo existente
            } else {
                // Se não, cria uma nova letra
                $currentLetter = $letters[$letterIndex] ?? ('Z' . ($letterIndex - count($letters) + 1));
                $usedGroups[$groupKey] = $currentLetter;
                $result[$currentLetter] = [$adj]; // Cria novo grupo
                $letterIndex++;
            }
        }
    }

    return $result;
}
function findQliForObject(array $object, array $QLI, $coverage = null): ?array
{

    if (!$coverage) {
        return collect($QLI)->first(function ($item) use ($object, $coverage) {
            $object_id = $item['object_id'] ?? $item['insured_object_id'] ?? null;
            return isset($item['coverageObject']['short_name'])
                && $item['coverageObject']['short_name'] === 'R.CIVIL OBR.'
                && $object_id === $object['id']
                && $item['status'];
        });
    } else {
        return collect($QLI)->first(function ($item) use ($object, $coverage) {
            $object_id = $item['object_id'] ?? $item['insured_object_id'] ?? null;
            return isset($item['coverageObject']['short_name'])
                && $item['coverageObject']['short_name'] === $coverage['short_name']
                && $object_id === $object['id']
                && $item['status'];
        });
    }
}

function findAdjustmentForQli(array $adjustments, string $annex, int $qliId): ?array
{
    return collect($adjustments[$annex] ?? [])->firstWhere('qli_id', $qliId);
}

function getRateRcFac(?array $adjustment): ?float
{
    if (!$adjustment) {
        return null;
    }
    //   dd($adjustment);
    $rate = CFG_rates_rc_options::find($adjustment['rate_id'] ?? null);
    return $rate?->rc_fac;
}
function findPremiumSaude($objects, $QLI, $relationship, $template)
{
    // dd($objects);
    $objects = collect($objects);
    $QLI = collect($QLI);

    // Encontra todos os objetos com o relationship especificado
    $matchingObjects = $objects->where('relationship', $relationship);

    if ($matchingObjects->isEmpty()) {
        return 0;
    }

    $total = 0;

    // Para cada objeto com o relationship correto, procura a taxa correspondente
    foreach ($matchingObjects as $object) {
        $qliItem = $QLI->where('object_id', $object['id'])->first();

        if ($qliItem && isset($qliItem['ratesObject'])) {
            $rates = collect($qliItem['ratesObject']);
            $rate = $rates->where('package', $template['package'])->first();

            if ($rate && isset($rate['premium_value'])) {
                $total += $rate['premium_value'];
            }
        }
    }

    // Retorna o total em vez de apenas o primeiro valor encontrado
    // dd($total);
    return $total;
}



function rcFacBC($object, $coverage, $annex, $quotation_id = null, $version_code = null)
{
    // Early return if no quotation_id
    if (!$quotation_id) {
        return null;
    }

    try {
        $objectService = new \App\Services\OBJ\OBJObjectsService();
        $objectUniqueManager = new \App\Services\QUO\QUOObjectUniqueManager($objectService);
        $QUOQuotationsService = new \App\Services\QUO\QUOQuotationsService($objectUniqueManager);
        $QUOObjectRiskService = new \App\Services\QUO\QUOObjectRiskService();

        // Get quotation versions
        $quotationData = $QUOQuotationsService->getById($quotation_id);

        if (empty($quotationData['data']['versions'])) {
            return null;
        }

        // Find version by annex/table_code
        $version = collect($quotationData['data']['versions'])->first(function ($version) use ($annex, $version_code) {
            $sameAnnex = ($version['table_code'] ?? null) == $annex;
            if (!$sameAnnex) {
                return false;
            }
            if (!$version_code) {
                return true;
            }
            return (string) ($version['version'] ?? '') === (string) $version_code;
        });

        if (!$version) {
            return null;
        }

        // Get risks for the version
        $risksData = $QUOObjectRiskService->getByVersion($version['id']);

        if (empty($risksData['data'])) {
            return null;
        }

        // Find RC Civil risk for the specific object
        $rcFac = collect($risksData['data'])->first(function ($item) use ($object) {
            $objectId = $item['object_id'] ?? $item['insured_object_id'] ?? null;

            $coverage = CFG_coverages::find($item['coverage_id']);

            return $coverage &&
                $coverage->short_name === 'R.CIVIL' &&
                $objectId === $object['id'];
        });

        return $rcFac['applied_rate'] ?? null;

    } catch (\Exception $e) {
        // Log error if needed
        // Log::error('Error in rcFacBC: ' . $e->getMessage());
        return null;
    }
}

function calculatePremiumWithSurcharge(
    $object,
    $coverage,
    $QLI,
    $annexsAdjustments,
    $annex,
    $quotation_id = null,
    $version_code = null
) {
    // Fallback: quando não há quotation_id, usa o cálculo base já exibido nas linhas do anexo
    if (!$quotation_id) {
        return checkPremium($object ?? [], $coverage ?? [], $QLI ?? null, $annexsAdjustments);
    }

    try {
        $objectService = new \App\Services\OBJ\OBJObjectsService();
        $objectUniqueManager = new \App\Services\QUO\QUOObjectUniqueManager($objectService);
        $QUOQuotationsService = new \App\Services\QUO\QUOQuotationsService($objectUniqueManager);
        $QUOObjectRiskService = new \App\Services\QUO\QUOObjectRiskService();

        // Get quotation versions
        $quotationData = $QUOQuotationsService->getById($quotation_id);

        if (empty($quotationData['data']['versions'])) {
            return checkPremium($object ?? [], $coverage ?? [], $QLI ?? null, $annexsAdjustments);
        }

        // Find version by annex/table_code
        $version = collect($quotationData['data']['versions'])->first(function ($version) use ($annex, $version_code) {
            $sameAnnex = ($version['table_code'] ?? null) == $annex;
            if (!$sameAnnex) {
                return false;
            }
            if (!$version_code) {
                return true;
            }
            return (string) ($version['version'] ?? '') === (string) $version_code;
        });

        if (!$version) {
            return checkPremium($object ?? [], $coverage ?? [], $QLI ?? null, $annexsAdjustments);
        }

        // Get risks for the version
        $risksData = $QUOObjectRiskService->getByVersion($version['id']);

        if (empty($risksData['data'])) {
            return checkPremium($object ?? [], $coverage ?? [], $QLI ?? null, $annexsAdjustments);
        }

        $risks = $risksData['data'];

        // Find specific risk for this object and coverage
        $risk = $risks->first(function ($risk) use ($object, $coverage) {
            return $risk['coverage_id'] == $coverage['id'] &&
                $risk['object_id'] == $object['id'];
        });

        // Find base RC item in QLI
        $rcBaseItem = collect($QLI)->first(function ($item) use ($object, $coverage) {
            $objectId = $item['object_id'] ?? $item['insured_object_id'] ?? null;

            return isset($item['coverageObject']['short_name']) &&
                $item['coverageObject']['short_name'] === $coverage['short_name'] &&
                $objectId === $object['id'] &&
                !empty($item['status']);
        });

        if (!$rcBaseItem) {
            return checkPremium($object ?? [], $coverage ?? [], $QLI ?? null, $annexsAdjustments);
        }

        $premiumComercial = $rcBaseItem['premiunComercial'] ?? 0;

        // Special handling for R.CIVIL OBR. coverage
        if ($coverage['short_name'] === 'R.CIVIL OBR.') {
            // Find RC Facultative risk
            $rcFacItem = $risks->first(function ($item) use ($object) {
                $objectId = $item['object_id'] ?? $item['insured_object_id'] ?? null;
                $coverage = CFG_coverages::find($item['coverage_id']);

                return $coverage &&
                    $coverage->short_name === 'R.CIVIL' &&
                    $objectId === $object['id'];
            });

            if ($rcFacItem && !empty($rcFacItem['rate_id'])) {
                $rate = CFG_rates_rc_options::find($rcFacItem['rate_id']);

                if ($rate && isset($rate['perc_rate'])) {
                    $increase = $premiumComercial * ($rate['perc_rate'] / 100);
                    return arredondarBase5($premiumComercial + $increase);
                }
            }
        }

        return $premiumComercial;

    } catch (\Exception $e) {
        return checkPremium($object ?? [], $coverage ?? [], $QLI ?? null, $annexsAdjustments);
    }
}
function generateQuotationNumber(int $insuranceBranchId, $salesChannelId): string
{
    $year = now()->year;
    $salesChannelId = str_pad($salesChannelId, 2, '0', STR_PAD_LEFT);

    $lockKey = "quotation_number_{$insuranceBranchId}_{$salesChannelId}_{$year}";

    return Cache::lock($lockKey, 10)->block(5, function () use ($insuranceBranchId, $salesChannelId, $year) {
        $branchCode = DB::table('cfg_insurance_branches')
            ->where('id', $insuranceBranchId)
            ->value('code') ?? '00';

        $lastQuotation = DB::table('quo_quotations')
            ->join('quo_quotation_versions', 'quo_quotations.id', '=', 'quo_quotation_versions.quotation_id')
            ->where('quo_quotations.insurance_branch_id', $insuranceBranchId)
            ->where('quo_quotation_versions.sales_channel_id', $salesChannelId)
            ->where('quo_quotations.quotation_number', 'LIKE', '(' . $year . '/' . $branchCode . '.' . $salesChannelId . '.%')
            ->whereNull('quo_quotations.deleted_at')
            ->orderByRaw("CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(quotation_number, '.', -1), ')', 1) AS UNSIGNED) DESC")
            ->first();

        $sequence = 1;
        if ($lastQuotation && $lastQuotation->quotation_number) {
            $number = $lastQuotation->quotation_number;

            $lastDotPos = strrpos($number, '.');
            $closingParenthesisPos = strrpos($number, ')');

            if ($lastDotPos !== false && $closingParenthesisPos !== false) {
                $sequenceStr = substr($number, $lastDotPos + 1, $closingParenthesisPos - $lastDotPos - 1);
                $sequence = intval($sequenceStr) + 1;
            } elseif (preg_match('/\((\d{4})\/(\d{2})\.(\d{2})\.(\d{5})\)/', $number, $matches)) {
                $sequence = intval($matches[4]) + 1;
            }
        }

        return sprintf('(%04d/%s.%s.%05d)', $year, $branchCode, $salesChannelId, $sequence);
    });
}
//     $qliFilter = findQliForObject($object, $QLI, $coverage);
//     if (!$qliFilter) {
//         return null;
//     }

//     $premiunComercial = $qliFilter['premiunComercial'] ?? ($qliFilter['coverage_premium'] ?? 0);
//     $premiunComercial = arredondarBase5($premiunComercial);
// dd($premiunComercial)
//     if ($coverage['short_name'] === 'R.CIVIL OBR.') {
//         $rcFacPremium = 0;
//         $matches = [];

//         foreach ($QLI as $item) {
//             $objectId = $item['object_id'] ?? $item['insured_object_id'] ?? null;
//             if (
//                 $objectId === $object['id'] &&
//                 isset($item['coverageObject']['short_name']) &&
//                 $item['coverageObject']['short_name'] === 'RC FACULTAT.'
//             ) {
//                 $matches[] = $item;
//             }
//         }

//         $match = null;
//         if ($annex) {
//             foreach ($matches as $item) {
//                 if (($item['annex'] ?? null) === $annex) {
//                     $match = $item;
//                     break;
//                 }
//             }
//         }

//         if (!$match && count($matches) === 1) {
//             $match = $matches[0];
//         }

//         if ($match) {
//             $rcFacPremium = $match['premiunComercial']
//                 ?? $match['coverage_premium']
//                 ?? $match['premium_value']
//                 ?? 0;
//         } else {
//             $adjustment = findAdjustmentForQli($annexsAdjustments, $annex, $qliFilter['id']);
//             dd($annexsAdjustments);

//             if ($adjustment && isset($adjustment['rate'])) {
//                 $increase = $premiunComercial * ($adjustment['rate'] / 100);
//                 return arredondarBase5($premiunComercial + $increase);
//             }
//         }

//         return arredondarBase5($premiunComercial + $rcFacPremium);
//     }

//     return $premiunComercial;
// }
// function calculatePremiumWithSurcharge(
//     $object,
//     $coverage,
//     $QLI,
//     $annexsAdjustments,
//     $annex
// ) {
//     // dd($annex);
//     $qliFilter = findQliForObject($object, $QLI, $coverage);
//     if (!$qliFilter) {
//         return null;
//     }

//     $premiunComercial = $qliFilter['premiunComercial'] ?? ($qliFilter['coverage_premium'] ?? 0);
//     $premiunComercial = arredondarBase5($premiunComercial);

//     if ($coverage['short_name'] === 'R.CIVIL OBR.') {
//         $rcFacPremium = 0;
//         $matches = [];

//         foreach ($QLI as $item) {
//             $objectId = $item['object_id'] ?? $item['insured_object_id'] ?? null;
//             if (
//                 $objectId === $object['id'] &&
//                 isset($item['coverageObject']['short_name']) &&
//                 $item['coverageObject']['short_name'] === 'RC FACULTAT.'
//             ) {
//                 $matches[] = $item;
//             }
//         }

//         $match = null;
//         if ($annex) {
//             foreach ($matches as $item) {
//                 if (($item['annex'] ?? null) === $annex) {
//                     $match = $item;
//                     break;
//                 }
//             }
//         }

//         if (!$match && count($matches) === 1) {
//             $match = $matches[0];
//         }

//         if ($match) {
//             $rcFacPremium = $match['premiunComercial']
//                 ?? $match['coverage_premium']
//                 ?? $match['premium_value']
//                 ?? 0;
//         } else {
//             $adjustment = findAdjustmentForQli($annexsAdjustments, $annex, $qliFilter['id']);
//             dd($annexsAdjustments);

//             if ($adjustment && isset($adjustment['rate'])) {
//                 $increase = $premiunComercial * ($adjustment['rate'] / 100);
//                 return arredondarBase5($premiunComercial + $increase);
//             }
//         }

//         return arredondarBase5($premiunComercial + $rcFacPremium);
//     }

//     return $premiunComercial;
// }

function checkLimitRCAnnex(array $object, array $QLI, array $annexsAdjustment, string $annex): ?float
{

    $rcFacArray = array_filter($risks->toArray(), function ($item) use ($object, $coverage) {
        $object_id = $item['object_id'] ?? $item['insured_object_id'] ?? null;
        return CFG_coverages::find($item['coverage_id'])->short_name === 'R.CIVIL' && $coverage['short_name'] === 'R.CIVIL OBR.'
            && $object_id === $object['id'];
    });

    $qliFilter = findQliForObject($object, $QLI);
    //  dd($qliFilter);
    $qliId = $qliFilter['id'] ?? null;

    $matches = [];
    foreach ($QLI as $item) {
        $objectId = $item['object_id'] ?? $item['insured_object_id'] ?? null;
        if (
            $objectId === $object['id'] &&
            isset($item['coverageObject']['short_name']) &&
            $item['coverageObject']['short_name'] === "R.CIVIL"
        ) {

            $matches[] = $item;
        }
    }
    dd("matches", $matches, $annex);
    $match = null;
    if ($annex) {
        foreach ($matches as $item) {
            // dd($item['annex']);
            if (($item['annex'] ?? null) === $annex) {
                $match = $item;
                break;
            }
        }
    }

    if (!$match && count($matches) === 1) {
        $match = $matches[0];
    }

    if ($match) {
        $rate = $match['rate'] ?? null;
        if (is_numeric($rate) && (float) $rate > 0) {
            return (float) $rate;
        }
        if (isset($match['ratesObject'][0]['rc_fac'])) {
            return (float) $match['ratesObject'][0]['rc_fac'];
        }
    }

    if ($qliId) {

        $adjustment = findAdjustmentForQli($annexsAdjustment, $annex, $qliId);
        if ($adjustment) {
            $rateId = $adjustment['rate_id'] ?? $adjustment['surcharge_id'] ?? null;
            if ($rateId) {
                $rate = CFG_rates_rc_options::find($rateId);
                if ($rate && isset($rate->rc_fac)) {
                    return (float) $rate->rc_fac;
                }
            }
        }
    }

    return null;
}

function checkLimitRC($object, $QLI, $anyAnnex)
{
    // dd($QLI);

    // $q = array_filter($QLI, function ($item) use ($object) {
    //     $object_id = $item['object_id'] ?? $item['insured_object_id'] ?? null;
    //     return isset($item['coverageObject']['short_name'])
    //         && $item['coverageObject']['short_name'] === 'RC FACULTAT.'
    //         && $object_id === $object['id'] && $item['status'];
    // });

    // $q = array_filter($QLI, function ($item) use ($object) {
    //     $object_id = $item['object_id'] ?? $item['insured_object_id'] ?? null;
    //     return isset($item['coverageObject']['short_name'])
    //         && $item['coverageObject']['short_name'] === 'R.CIVIL'
    //         && $object_id === $object['id'] && $item['status'];
    // });

    // if (count($q) && !$anyAnnex) {


    //     $q = array_values($q); // reorganiza índices
    //        dd($q);
    //     $rate = CFG_rates_rc_options::find($q[0]['rate_id']);
    //     $limit = $rate->rc_fac;
    //     return $limit;
    // } else {

    $q = array_filter($QLI, function ($item) use ($object) {
        $object_id = $item['object_id'] ?? $item['insured_object_id'] ?? null;
        return isset($item['coverageObject']['short_name'])
            && $item['coverageObject']['short_name'] === 'R.CIVIL OBR.'
            && $object_id === $object['id'] && $item['status'];
    });

    $q = array_values($q);

    // $premiumValue=0;git a
    if (isset($q[0]['premium_value'])) {
        $premiumValue = $q[0]['premium_value']; // 21000
        // $rates = $q[0]['ratesObject'];
        // $rateMatch = collect($rates)->firstWhere('rc_premium_kz', number_format($premiumValue, 2, '.', ''));
//  dd( );
        $premiumValue = $q[0]['ratesObject'][0]['min_rc_limit_kz'];


    } else {
        // dd($q);
    }
    //dd($premiumValue);
    if (isset($premiumValue)) {
        return $premiumValue;
    } else {
        return 0;
    }
    // }

}

// function checkLimitRC($object, $QLI)
// {
//     // dd($QLI);

//     $q = array_filter($QLI, function ($item) use ($object) {
//         $object_id = $item['object_id'] ?? $item['insured_object_id'] ?? null;
//         return isset($item['coverageObject']['short_name'])
//             && $item['coverageObject']['short_name'] === 'R.CIVIL OBR.'
//             && $object_id === $object['id'] && $item['status'];
//     });

//     $q = array_values($q);
//     //   dd(  vars: $q);
//     if (isset($q[0]['premium_value'])) {
//         $premiumValue = $q[0]['premium_value']; // 21000
//         $rates = $q[0]['ratesObject'];

//         $rateMatch = collect($rates)->firstWhere('rc_premium_kz', number_format($premiumValue, 2, '.', ''));

//         $premiumValue = $rateMatch['min_rc_limit_kz'];
//     }
//     return $premiumValue;


// }


function checkPremiumTotal($object, $coverages, $QLI, $anyAnnex)
{
    $totalPremium = 0;
    foreach ($coverages as $coverage) {
        $premium = 0;

        if ($coverage['short_name'] != 'R.CIVIL' && (selectedCoverage($QLI, $coverage['id']) && !$coverage['group_id'] || $coverage['short_name'] == 'R.CIVIL OBR.')) {
            $totalPremium += checkPremium($object ?? [], $coverage ?? [], $QLI ?? null, $anyAnnex);
        }
    }
    // }
    return $totalPremium;
}

function filterCoverages(array $coverages): array
{
    return array_filter($coverages, function ($coverage) {
        return $coverage['short_name'] !== 'RC FACULTAT.';
    });
}
function hasCoverage(array $coverages): array
{
    return array_filter($coverages, function ($coverage) {
        return $coverage['short_name'] !== 'RC FACULTAT.';
    });
}

function checkPremiumTotalByCoverage($objects, $coverage, $QLI, $anyAnnex)
{
    $totalPremium = 0;
    foreach ($objects as $object) {
        $totalPremium += checkPremium($object ?? [], $coverage ?? [], $QLI ?? null, $anyAnnex);
    }

    return $totalPremium;
}

function arredondarBase5($valor)
{
    // Verifica se é número inteiro (sem casa decimal)
    if ($valor == (int) $valor) {
        return $valor; // Mantém inalterado
    }

    // Separa parte inteira e decimal
    $parteInteira = (int) $valor;
    $parteDecimal = $valor - $parteInteira;

    // Arredonda por excesso se decimal ≥ 0.5
    if ($parteDecimal >= 0.5) {
        return ceil($valor); // Arredonda para cima
    } else {
        return $parteInteira; // Arredonda para baixo (mantém só parte inteira)
    }
}

function checkPremiumTotalGeral($objects, $coverages, $QLI, $anyAnnex)
{
    $totalPremium = 0;

    foreach ($objects as $object) {
        $totalPremium += checkPremiumTotal(
            $object ?? [],
            filterCoverages($coverages) ?? [],
            $QLI ?? null,
            $anyAnnex
        );
    }

    return $totalPremium;
}


function templatesData()
{
    $results = DB::table('cfg_templates')
        ->select(
            'cfg_templates.*',
            'cfg_coverages.*',
            'cfg_insurance_branches.name as branch_name',
            'coverage_branches.name as coverage_branch_name'
        )
        ->join('cfg_insurance_branches', 'cfg_templates.insurance_branch_id', '=', 'cfg_insurance_branches.id')
        ->join('cfg_template_coverage', 'cfg_templates.id', '=', 'cfg_template_coverage.template_id')
        ->join('cfg_coverages', 'cfg_template_coverage.coverage_id', '=', 'cfg_coverages.id')
        ->join('cfg_insurance_branches as coverage_branches', 'cfg_coverages.insurance_branch_id', '=', 'coverage_branches.id')
        ->where('cfg_templates.active', true)
        ->where('cfg_coverages.active', true)
        ->where('cfg_insurance_branches.active', true)
        ->where('coverage_branches.active', true)
        ->whereIn('cfg_insurance_branches.code', [90, 91])
        ->whereIn('cfg_coverages.short_name', ['C.C.C.', 'I.R.E.', 'ACTOS VAND.']) // ← CORREÇÃO AQUI
        ->whereNull('cfg_templates.deleted_at')
        ->whereNull('cfg_coverages.deleted_at')
        ->whereNull('cfg_insurance_branches.deleted_at')
        ->whereNull('coverage_branches.deleted_at')
        ->orderByRaw('CASE WHEN cfg_templates.sequence IS NULL THEN 1 ELSE 0 END')
        ->orderBy('cfg_templates.sequence')
        ->orderBy('cfg_templates.code')
        ->get();

    return $results;
}
function getCapitalToCoverageTypeZero($coverage, $rates, $premium, $rate_id)
{
    if ($coverage["short_name"] == "DESP.TRAT." && $premium) {
        $rate = collect($rates)->firstWhere("id", $rate_id);
        return $rate ? $rate["dt"] : 0;
    }
    if (($coverage["short_name"] == "MIP       +" || $coverage["short_name"] == "MIP") && $premium) {
        $rate = collect($rates)->firstWhere("id", $rate_id);
        return $rate ? $rate["mip"] : 0;
    }
    if ($coverage["short_name"] == "DESP.FUN." && $premium) {
        $rate = collect($rates)->firstWhere("id", $rate_id);
        return $rate ? $rate["df"] : 0;
    }
    return 0;
}


// function checkPremiumTotalGeral($QLI)
// {
//     $total = array_reduce($QLI, function ($total, $item) {
//         $premiumValue = isset($item['premiunComercial'])
//             ? $item['premiunComercial']
//             : $item['coverage_premium'];

//         // Só soma se não for RC FACULTAT.
//         if ($item['coverageObject']['short_name'] !== 'RC FACULTAT.') {
//             $total += ($premiumValue ?? 0);
//         }

//         return $total;
//     }, 0);

//     return $total;
// }

if (!function_exists('get_surchargeById')) {
    /**
     * Retorna um registro da model CFG_rates_rc_options pelo id.
     *
     * @param int $id
     * @return \App\Models\CFG_rates_rc_options|null
     */
    function get_surchargeById(int $id)
    {
        return CFG_rates_rc_options::find($id);
    }
}
function get_surchargeById(int $id)
{
    return CFG_rates_rc_options::find($id);
}

function findDeductible($id_deductible)
{
    $deductible = CFG_deductibles::find($id_deductible);
    return $deductible;
}

function getPremioOuLimite($tipo)
{
    // Lista de mapeamentos
    $mapa = [
        "DANOS PRÓPRIOS" => "Prémio DP <br>(KZ)",
        "OCUPANTES DE VIATURAS" => "Prémio OC<br>(KZ)",
        "RESPONSABILIDADE CIVIL OBRIGATÓRIA" => "Prémio RC<br>(KZ)",
        "RESPONSABILIDADE CIVIL FACULTATIVA" => "Limite RC<br>(KZ)"
    ];

    // Normalizar entrada (tirar espaços extras e capitalização)
    $tipoNormalizado = strtoupper(trim($tipo));

    // Verifica se existe no mapa
    return $mapa[$tipoNormalizado] ?? "Tipo de cobertura não reconhecido";
}


function addColumn()
{
    if (!Schema::hasColumn('sub_coverages', 'coverage_perc')) {
        Schema::table('sub_coverages', function (Blueprint $table) {
            $table->double('coverage_perc')->nullable()->after(column: 'premium_value');
        });
    }
}
function numberToWords($number)
{
    $units = ["", "um", "dois", "três", "quatro", "cinco", "seis", "sete", "oito", "nove"];
    $teens = ["dez", "onze", "doze", "treze", "quatorze", "quinze", "dezesseis", "dezessete", "dezoito", "dezenove"];
    $tens = ["", "dez", "vinte", "trinta", "quarenta", "cinquenta", "sessenta", "setenta", "oitenta", "noventa"];
    $hundreds = ["", "cem", "duzentos", "trezentos", "quatrocentos", "quinhentos", "seiscentos", "setecentos", "oitocentos", "novecentos"];

    if ($number == 0)
        return "Zero";
    if ($number < 0)
        return "Menos " . numberToWords(abs($number));

    $text = "";

    // Thousands
    if ($number >= 1000) {
        $thousands = intval($number / 1000);
        if ($thousands == 1) {
            $text .= "mil";
        } else {
            $text .= numberToWords($thousands) . " mil";
        }
        $number %= 1000;
        if ($number > 0)
            $text .= " e ";
    }

    // Hundreds
    if ($number >= 100) {
        $hundred = intval($number / 100);
        if ($number % 100 == 0 && $hundred == 1) {
            $text .= "cem";
        } else {
            $text .= $hundreds[$hundred];
        }
        $number %= 100;
        if ($number > 0)
            $text .= " e ";
    }

    // Tens
    if ($number >= 20) {
        $ten = intval($number / 10);
        $text .= $tens[$ten];
        $number %= 10;
        if ($number > 0)
            $text .= " e ";
    }

    // Teens (10-19)
    if ($number >= 10 && $number < 20) {
        $text .= $teens[$number - 10];
        $number = 0;
    }

    // Units
    if ($number > 0 && $number < 10) {
        $text .= $units[$number];
    }

    // Capitalize first letter
    return ucfirst($text);
}

// Example usage:
// echo numberToWords(1234); // Output: "Mil duzentos e trinta e quatro"

if (!function_exists('dataPortugues')) {
    function dataPortugues($format = 'Y')
    {
        Carbon::setLocale('pt');
        return now()->translatedFormat($format);
    }
}
if (!function_exists('formatarNumeroCotacao')) {
    function formatarNumeroCotacao($numero, $digitos = 7)
    {
        // Se o número já tiver parênteses, removê-los
        $numero = trim($numero, "()");

        // Extrair a sequência (última parte após o último ponto)
        $partes = explode('.', $numero);

        if (count($partes) > 0) {
            // Pegar a última parte
            $sequencia = end($partes);

            // Se houver algo após a sequência (como um sufixo), removê-lo
            if (strpos($sequencia, '-') !== false) {
                $sequencia = explode('-', $sequencia)[0];
            }

            return str_pad($sequencia, $digitos, '0', STR_PAD_LEFT);
        }

        // Fallback: se não conseguir extrair, retorna o número original formatado
        return str_pad($numero, $digitos, '0', STR_PAD_LEFT);
    }
}

function groupCoverages($group_id, $coverages)
{
    // Garante que seja array
    $coverages = is_array($coverages) ? $coverages : $coverages->toArray();

    // Filtra pelo grupo
    $groupCoverages = array_filter($coverages, function ($coverage) use ($group_id) {
        return $coverage['group_id'] === $group_id;
    });

    // Ordena pelo campo "seq"
    usort($groupCoverages, function ($a, $b) {
        return $a['seq'] <=> $b['seq']; // ascendente
    });

    return array_values($groupCoverages); // reindexa
}
function calcularPremios($premiumTotal, $campo)
{
    // Garantir número
    $premioSimples = floatval($premiumTotal / 1.418);

    // Cálculos
    $premioEncargos = round($premioSimples * 0.20, 2);
    $premioFundoGarantia = round($premioSimples * 0.05, 2);
    $premioComercial = round($premioSimples + $premioEncargos, 2);
    $iva = round($premioComercial * 0.14, 2);
    $comercialMaisIva = round($iva + $premioComercial, 2);

    $premioTotalAnual = round($premioFundoGarantia + $comercialMaisIva, 2);
    $premioTotalSemestre = round((($comercialMaisIva * 1.03) + $premioFundoGarantia) / 2, 2);
    $premioTotalTrimestre = round((($comercialMaisIva * 1.05) + $premioFundoGarantia) / 4, 2);
    $premioTotalMensal = round((($comercialMaisIva * 1.07) + $premioFundoGarantia) / 12, 2);

    // Array com todos os valores
    $dados = [
        'premio_simples' => $premioSimples,
        'premio_encargos' => $premioEncargos,
        'premio_fundo_garantia' => $premioFundoGarantia,
        'premio_comercial' => $premioComercial,
        'iva' => $iva,
        'comercial_mais_iva' => $comercialMaisIva,
        'total_anual' => $premioTotalAnual,
        'total_semestre' => $premioTotalSemestre,
        'total_trimestre' => $premioTotalTrimestre,
        'total_mensal' => $premioTotalMensal,
    ];

    // Retorna apenas o campo pedido
    return $dados[$campo] ?? null;
}

function getRateCodes(array $items): array
{
    // Primeiro verificar a estrutura dos items
    if (empty($items)) {
        return [];
    }

    // Verificar se os items têm a estrutura esperada
    $filteredItems = [];
    foreach ($items as $item) {
        // Verificar se $item é um array e se tem a chave 'rate_code'
        if (is_array($item) && array_key_exists('rate_code', $item) && !is_null($item['rate_code'])) {
            $filteredItems[] = $item;
        }
    }

    return array_column($filteredItems, 'rate_code');
}
function temPesados(array $items)
{
    // Primeiro verificar a estrutura dos items
    if (empty($items)) {
        return false;
    }

    // Verificar se os items têm a estrutura esperada
    foreach ($items as $item) {
        // dd($items);
// dd($item);

        // Verificar se $item é um array e se tem a chave 'rate_code'
        // if (isset($item['weight']) && $item['weight'] > 3600 || $item['type']=="Camião") {

        if (isset($item['weight']) && $item['weight'] > 3600 ) {
            return true;
        }
    }

    return false;
}

function getValoresPorTipo($rates)
{
    $valores = [];

    foreach ($rates as $rate) {
        $texto = strtolower(
            ($rate->description ?? '') . ' ' .
            ($rate->category ?? '') . ' ' .
            ($rate->vehicle_type ?? '')
        );
// dd($texto);
        // if (str_contains($texto, 'ligeiro')) {

        // }

        if (str_contains($texto, 'pesado') || str_contains($texto, 'camião')|| str_contains($texto, 'autocarro')) {
            $valores[] = 750000;
        } else {
            $valores[] = 500000;
        }
    }

    // Remove duplicados (mantém apenas um de cada tipo)
    return array_values(array_unique($valores));
}

function sortCoverages($coverages)
{
    $c = collect($coverages)
        ->sortBy(function ($item) {
            return [
                is_null($item['group_id']) || $item['short_name'] == 'R.CIVIL OBR.' ? 0 : 1,
                $item['seq'],
            ];
        })
        ->values()
        ->toArray();
    // dd( $c);
    return $c;
}

// Função para gerar título da cotação
function generateQuoteTitle($entity, $coverages, $cotNumber, $version_number, $QLI)
{
    $coverasSingla = '';
    $count = 0;

    if (isset($coverages)) {
        foreach ($coverages as $cov) {
            if (selectedCoverage($QLI, $cov['id']) && !$cov['group_id']) {

                // Pega apenas o primeiro trecho antes de "_" ou espaço
                $shortName = preg_split('/[_\s]/', $cov['short_name'])[0];
                if ($shortName == 'R.CIVIL') {
                    $shortName = 'RC';
                }
                $coverasSingla .= $count > 0
                    ? ('+' . $shortName)
                    : $shortName;

                $count++;
            }
        }
    }

    return "Cotação_" .
        Str::upper($entity['name'] ?? '') .
        "_AUTO_" .
        $coverasSingla .
        "_Nº " . $cotNumber .
        ($version_number > 1 ? "_V" . $version_number : "");
}


// Função para formatar valor do objeto
function formatObjectValue($object, $td)
{
    if ($td['name'] === 'capital_value' || $td['name'] === 'engine_min') {
        if ($td['name'] === 'engine_min' && isset($object['cilinder_cc'])) {
            return number_format($object['cilinder_cc'] ?? 0, 0, ',', '.');
        }
        return number_format($object[$td['name']] ?? 0, 0, ',', '.');
    } elseif ($td['name'] === 'brand_model') {
        return ($object['brand'] ?? '') . ' ' . ($object[$td['name']] ?? '');
    }
    return $object[$td['name']] ?? '';
}

// Função para obter dados de sobretaxa
// function getSurchargeData($surcharges, $objectId, $annex)
// {
//     $surcharge = collect($surcharges)
//         ->where('object_id', $objectId)
//         ->where('annex', $annex)
//         ->first();

//     return $surcharge ? get_surchargeById($surcharge['surcharge_id']) : null;
// }

// Função para calcular limite RC
function calculateLimitRC($object, $QLI, $surchargeData, $anyAnnex)
{
    // dd("ola");
    $r = $surchargeData ? $surchargeData["rc_fac"] : checkLimitRC($object ?? [], $QLI ?? null, $anyAnnex);

    return $r;
}

// Função para calcular prêmio com sobretaxa
// calculatePremiumWithSurcharge(
//                                                 $object, 
//                                                 $coverage, 
//                                                 $QLI, 
//                                                 $surchargeData,
//                                             count($surcharges)
//                                             );


// Função para calcular total do objeto
function calculateObjectPremiumTotal(
    $object,
    $coverages,
    $QLI,
    $annexsAdjustments,
    $annex,
    $quotation_id = null,
    $version_code = null
) {
    $total = 0;
    // $surchargeData = getSurchargeData($surcharges, $object['id'], $annex);

    foreach (filterCoverages($coverages) as $coverage) {
        if ($coverage['short_name'] != 'R.CIVIL' && (selectedCoverage($QLI, $coverage['id']) && !$coverage['group_id'] || $coverage['short_name'] == 'R.CIVIL OBR.')) {

            $total += calculatePremiumWithSurcharge(
                $object,
                $coverage,
                $QLI,
                $annexsAdjustments,
                $annex,
                $quotation_id,
                $version_code
            );

        }
    }

    return arredondarBase5($total);
}

// Função para calcular total por cobertura
function getSurchargeData($surcharges, $objectId, $annex)
{
    $surcharge = collect($surcharges)
        ->where('object_id', $objectId)
        ->where('annex', $annex)
        ->first();

    return $surcharge ? get_surchargeById($surcharge['surcharge_id']) : null;
}

function calculateTotalCoverage(
    $objects,
    $coverage,
    $QLI,
    $annexsAdjustments,
    $annex,
    $quotation_id = null,
    $version_code = null
) {
    $total = 0;

    foreach ($objects as $object) {
        // dd($object['id']);
        $premium = calculatePremiumWithSurcharge(
            $object,
            $coverage,
            $QLI,
            $annexsAdjustments,
            $annex,
            $quotation_id,
            $version_code
        );

        $total += $premium;
    }
    $total = arredondarBase5($total);
    return $total;
}
// Função para calcular total geral
function calculateTotalGeral($objects, $coverages, $QLI, $surcharges, $annex, $quotation_id = null)
{
    $total = 0;

    foreach ($objects as $object) {
        $total += calculateObjectPremiumTotal($object, $coverages, $QLI, $surcharges, $annex, $quotation_id);
    }

    return $total;
}




/**
 * ============================================================================
 * FUNÇÕES HELPER PARA CÁLCULO DE PRÉMIOS DE SEGURO DE SAÚDE BIC
 * ============================================================================
 * 
 * Sistema: BIC Seguros - Cálculo de Prémios de Saúde em Grupo
 * Data: 03/12/2025
 * 
 * IMPORTANTE: A BIC aplica a margem comercial DUAS VEZES!
 * 
 * FÓRMULA COMPLETA:
 * Prémio Final = Prémio RISCO × Câmbio × (1 + Margem)² × Agravamento
 * Prémio Final = Prémio RISCO × 1.915 × 1.5625 × 1.625
 * Prémio Final = Prémio RISCO × 4.862305
 * 
 * Exemplo (Lúcio 44 anos, Male, BIC Pleno, 8 pessoas):
 * 67.551,77 × 4.862 = 328.457 KZ ✅
 * 
 * ============================================================================
 */

if (!function_exists('getInsuranceExchangeRate')) {
    /**
     * Obtém a taxa de câmbio do .env
     * 
     * Taxa de 2017: 137.60 KZ/USD
     * Taxa de 2025: 263.49 KZ/USD
     * Fator: 263.49 / 137.60 = 1.915
     * 
     * @return float Taxa de câmbio
     */
    function getInsuranceExchangeRate(): float
    {
        return floatval(env('INSURANCE_EXCHANGE_RATE', 1.915));
    }
}

if (!function_exists('getInsuranceDefaultMargin')) {
    /**
     * Obtém a margem comercial padrão do .env
     * 
     * IMPORTANTE: Esta margem é aplicada DUAS VEZES!
     * 
     * @return float Margem comercial (0.25 = 25%)
     */
    function getInsuranceDefaultMargin(): float
    {
        return floatval(env('INSURANCE_DEFAULT_MARGIN', 0.25));
    }
}

if (!function_exists('getInsuranceSizeFactor')) {
    /**
     * Obtém o fator de agravamento baseado no tamanho do grupo
     * 
     * @param int $groupSize Número de pessoas no grupo
     * @return float Fator de agravamento
     */
    function getInsuranceSizeFactor(int $groupSize): float
    {
        $factorsString = env('INSURANCE_SIZE_FACTORS', '');

        if (!empty($factorsString)) {
            $factorsArray = explode(',', $factorsString);

            foreach ($factorsArray as $factor) {
                $parts = explode(':', trim($factor));

                if (count($parts) === 3) {
                    $min = intval($parts[0]);
                    $max = intval($parts[1]);
                    $factorValue = floatval($parts[2]);

                    if ($groupSize >= $min && $groupSize <= $max) {
                        return $factorValue;
                    }
                }
            }
        }

        // Fallback: Tabela padrão
        $defaultFactors = [
            ['min' => 2, 'max' => 19, 'factor' => 1.625],
            ['min' => 20, 'max' => 49, 'factor' => 1.100],
            ['min' => 50, 'max' => 99, 'factor' => 1.000],
            ['min' => 100, 'max' => 249, 'factor' => 0.900],
            ['min' => 250, 'max' => 999, 'factor' => 0.825],
            ['min' => 1000, 'max' => 999999, 'factor' => 0.800],
        ];

        foreach ($defaultFactors as $range) {
            if ($groupSize >= $range['min'] && $groupSize <= $range['max']) {
                return $range['factor'];
            }
        }

        return 1.625;
    }
}

if (!function_exists('getClientMargin')) {
    /**
     * Busca margem customizada para um cliente específico pelo NIF
     * 
     * @param string $clientNIF NIF do cliente
     * @return float|null Margem customizada ou null se não encontrar
     */
    function getClientMargin(string $clientNIF): ?float
    {
        if (empty($clientNIF)) {
            return null;
        }

        $marginsString = env('INSURANCE_CUSTOM_MARGINS', '');

        if (empty($marginsString)) {
            return null;
        }

        $marginsArray = explode(',', $marginsString);

        foreach ($marginsArray as $margin) {
            $parts = explode(':', trim($margin));

            if (count($parts) === 2) {
                $nif = trim($parts[0]);
                $marginValue = floatval($parts[1]);

                if ($nif === $clientNIF) {
                    return $marginValue;
                }
            }
        }

        return null;
    }
}

if (!function_exists('calculateInsurancePremium')) {
    /**
     * Calcula o prémio final aplicando todos os fatores
     * 
     * FÓRMULA: Prémio RISCO × Câmbio × (1 + Margem)² × Agravamento
     * 
     * IMPORTANTE: A margem é aplicada DUAS VEZES!
     * Isto replica exatamente o sistema da BIC Seguros.
     * 
     * Exemplo para grupo de 8 pessoas, margem 25%:
     * Prémio = 67.552 × 1.915 × 1.5625 × 1.625 = 328.457 KZ
     * 
     * @param float $premiumRisk Prémio base de risco de 2017 (da BD)
     * @param int $groupSize Tamanho do grupo segurado
     * @param float|null $customMargin Margem customizada (opcional)
     * @param float|null $customExchangeRate Taxa câmbio customizada (opcional)
     * @return float Prémio final calculado e arredondado
     */
    function calculateInsurancePremium(
        float $premiumRisk,
        int $groupSize,
        ?float $customMargin = null,
        ?float $customExchangeRate = null
    ): float {
        // 1. Obter configurações
        $exchangeRate = $customExchangeRate ?? getInsuranceExchangeRate();
        $sizeFactor = getInsuranceSizeFactor($groupSize);
        $margin = $customMargin ?? getInsuranceDefaultMargin();

        // 2. Calcular prémio final COM MARGEM APLICADA DUAS VEZES
        // Fórmula: base × câmbio × (1 + margem)² × agravamento
        $marginFactor = (1 + $margin) * (1 + $margin); // Margem ao quadrado
        $premiumFinal = $premiumRisk * $exchangeRate * $marginFactor * $sizeFactor;

        // 3. Arredondar para 2 casas decimais
        return round($premiumFinal, 2);
    }
}

if (!function_exists('formatInsurancePremium')) {
    /**
     * Formata um prémio para exibição com separador de milhares
     * 
     * @param float $premium Valor do prémio
     * @return string Prémio formatado (ex: "328.439")
     */
    function formatInsurancePremium(float $premium): string
    {
        return number_format($premium, 0, ',', '.');
    }
}

if (!function_exists('calculateGroupTotalPremium')) {
    /**
     * Calcula o prémio total de um grupo de segurados
     * 
     * @param array $insuredPersons Array de pessoas com ['name', 'premium_risk']
     * @param float|null $customMargin Margem customizada (opcional)
     * @return array ['total', 'average', 'group_size', 'details']
     */
    function calculateGroupTotalPremium(array $insuredPersons, ?float $customMargin = null): array
    {
        $groupSize = count($insuredPersons);
        $total = 0;
        $details = [];

        foreach ($insuredPersons as $person) {
            $premiumRisk = $person['premium_risk'] ?? 0;
            $premiumFinal = calculateInsurancePremium($premiumRisk, $groupSize, $customMargin);

            $details[] = [
                'name' => $person['name'] ?? 'N/A',
                'premium_risk' => $premiumRisk,
                'premium_final' => $premiumFinal,
                'premium_formatted' => formatInsurancePremium($premiumFinal)
            ];

            $total += $premiumFinal;
        }

        return [
            'total' => round($total, 2),
            'average' => round($total / $groupSize, 2),
            'group_size' => $groupSize,
            'details' => $details
        ];
    }
}

if (!function_exists('getInsuranceConfig')) {
    /**
     * Retorna as configurações atuais do sistema (útil para debug)
     * 
     * @param int $groupSize Tamanho exemplo para calcular agravamento
     * @return array Todas as configurações atuais
     */
    function getInsuranceConfig(int $groupSize = 8): array
    {
        $exchangeRate = getInsuranceExchangeRate();
        $margin = getInsuranceDefaultMargin();
        $sizeFactor = getInsuranceSizeFactor($groupSize);
        $marginSquared = (1 + $margin) * (1 + $margin);
        $totalMultiplier = $exchangeRate * $marginSquared * $sizeFactor;

        return [
            'exchange_rate' => $exchangeRate,
            'margin' => $margin,
            'margin_percent' => ($margin * 100) . '%',
            'margin_squared' => $marginSquared,
            'size_factor' => $sizeFactor,
            'group_size_example' => $groupSize,
            'formula' => "Prémio RISCO × {$exchangeRate} × {$marginSquared} × {$sizeFactor}",
            'formula_detailed' => "Prémio RISCO × {$exchangeRate} × (1+{$margin})² × {$sizeFactor}",
            'total_multiplier' => round($totalMultiplier, 6),
            'note' => 'IMPORTANTE: A margem é aplicada DUAS VEZES!'
        ];
    }
}

if (!function_exists('debugPremiumCalculation')) {
    /**
     * Debug completo do cálculo de prémio (mostra todos os passos)
     * 
     * @param float $premiumRisk Prémio base de risco
     * @param int $groupSize Tamanho do grupo
     * @param float|null $customMargin Margem customizada (opcional)
     * @return array Detalhes completos do cálculo passo a passo
     */
    function debugPremiumCalculation(float $premiumRisk, int $groupSize, ?float $customMargin = null): array
    {
        $exchangeRate = getInsuranceExchangeRate();
        $sizeFactor = getInsuranceSizeFactor($groupSize);
        $margin = $customMargin ?? getInsuranceDefaultMargin();
        $marginSquared = (1 + $margin) * (1 + $margin);
        $finalPremium = calculateInsurancePremium($premiumRisk, $groupSize, $customMargin);

        // Cálculos intermediários
        $step1Result = $premiumRisk;
        $step2Result = round($premiumRisk * $exchangeRate, 2);
        $step3Result = round($step2Result * $marginSquared, 2);
        $step4Result = round($step3Result * $sizeFactor, 2);

        return [
            'premium_risk' => $premiumRisk,
            'exchange_rate' => $exchangeRate,
            'margin' => $margin,
            'margin_percent' => ($margin * 100) . '%',
            'margin_squared' => $marginSquared,
            'size_factor' => $sizeFactor,
            'group_size' => $groupSize,
            'calculation_steps' => [
                'step_1' => "{$step1Result} KZ (Prémio RISCO de 2017)",
                'step_2' => "{$step1Result} × {$exchangeRate} = {$step2Result} KZ (após câmbio)",
                'step_3' => "{$step2Result} × {$marginSquared} = {$step3Result} KZ (após margem²)",
                'step_4' => "{$step3Result} × {$sizeFactor} = {$step4Result} KZ (após agravamento)"
            ],
            'formula' => "{$premiumRisk} × {$exchangeRate} × {$marginSquared} × {$sizeFactor}",
            'formula_expanded' => "{$premiumRisk} × {$exchangeRate} × (1+{$margin})² × {$sizeFactor}",
            'final_premium' => $finalPremium,
            'final_premium_formatted' => formatInsurancePremium($finalPremium),
            'note' => '⚠️ A margem (25%) é aplicada DUAS VEZES!'
        ];
    }
}



/**
 * Gera múltiplas cotações baseadas nos pacotes disponíveis no ratesObject
 * 
 * @param array $originalQuotation A cotação original com a estrutura completa
 * @return array Array de cotações, uma para cada pacote disponível
 */
function generateQuotationsByPackage($originalQuotation)
{
    if (!isset($originalQuotation['line_items']) || !is_array($originalQuotation['line_items'])) {
        return ['A' => $originalQuotation];
    }

    // Identificar qual é o pacote original baseado no rate_id atual de cada QLI
    $originalPackage = identifyOriginalPackage($originalQuotation);

    // Coletar todos os pacotes únicos disponíveis nos ratesObject
    $allPackages = [];

    foreach ($originalQuotation['line_items'] as $item) {
        if (isset($item['ratesObject']) && is_array($item['ratesObject'])) {
            foreach ($item['ratesObject'] as $rate) {
                if (isset($rate['package'])) {
                    $allPackages[$rate['package']] = true;
                }
            }
        }
    }

    $packages = array_keys($allPackages);

    if (empty($packages)) {
        return ['A' => $originalQuotation];
    }

    // Ordenar pacotes alfabeticamente para consistência
    sort($packages);

    // Se encontramos o pacote original, colocá-lo como primeiro (A)
    $packagesWithOriginalFirst = [];
    if ($originalPackage && in_array($originalPackage, $packages)) {
        $packagesWithOriginalFirst[] = $originalPackage;
        // Remover o pacote original da lista para adicionar os outros depois
        $otherPackages = array_filter($packages, function ($pkg) use ($originalPackage) {
            return $pkg !== $originalPackage;
        });
        sort($otherPackages);
        $packagesWithOriginalFirst = array_merge($packagesWithOriginalFirst, $otherPackages);
    } else {
        $packagesWithOriginalFirst = $packages;
    }

    // Gerar uma cotação para cada pacote
    $quotations = [];
    $versionCounter = 1; // Começar com 1 para a cotação original

    foreach ($packagesWithOriginalFirst as $index => $packageName) {
        $quotationIndex = chr(65 + $index); // A, B, C, ...

        // Criar cópia profunda da cotação original
        $newQuotation = $originalQuotation;

        // Atualizar version_number
        $newQuotation['version_number'] = $versionCounter;
        $versionCounter++;

        // Resetar line_items para reconstruir
        $newQuotation['line_items'] = [];

        foreach ($originalQuotation['line_items'] as $originalItem) {
            $newItem = $originalItem;

            // Encontrar a rate correspondente a este pacote
            $selectedRate = null;
            $selectedRateIndex = null;

            if (isset($originalItem['ratesObject']) && is_array($originalItem['ratesObject'])) {
                foreach ($originalItem['ratesObject'] as $rateIndex => $rate) {
                    if (isset($rate['package']) && $rate['package'] === $packageName) {
                        $selectedRate = $rate;
                        $selectedRateIndex = $rateIndex;
                        break;
                    }
                }
            }

            if ($selectedRate) {
                // Atualizar os campos com os dados do pacote selecionado
                $newItem['coverage_premium'] = (float) $selectedRate['premium_value'];
                $newItem['rate_id'] = $selectedRate['id'];
                $newItem['applied_rate'] = (float) $selectedRate['premium_value'];

                // Manter apenas a rate correspondente ao pacote no ratesObject
                if ($selectedRateIndex !== null) {
                    $newItem['ratesObject'] = [$selectedRate];
                }
            } else {
                // Se não encontrar rate para este pacote, usar a rate original
                $currentRateId = $originalItem['rate_id'] ?? null;
                $foundRate = null;

                if (isset($originalItem['ratesObject']) && is_array($originalItem['ratesObject'])) {
                    foreach ($originalItem['ratesObject'] as $rate) {
                        if (isset($rate['id']) && $rate['id'] == $currentRateId) {
                            $foundRate = $rate;
                            break;
                        }
                    }
                }

                if ($foundRate) {
                    // Manter apenas a rate encontrada
                    $newItem['ratesObject'] = [$foundRate];
                } else {
                    // Se não encontrar nenhuma, manter o ratesObject completo
                    $newItem['ratesObject'] = $originalItem['ratesObject'] ?? [];
                }
            }

            $newQuotation['line_items'][] = $newItem;
        }

        // Recalcular premiosGlobais usando a lógica exata fornecida
        $newQuotation['premiosGlobais'] = recalculatePremiosGlobais($newQuotation);

        // Adicionar metadados sobre o pacote
        $newQuotation['package_name'] = $packageName;
        $newQuotation['table'] = $quotationIndex;
        $newQuotation['package_count'] = count($packagesWithOriginalFirst);
        $newQuotation['is_original_package'] = ($packageName === $originalPackage);

        $quotations[$quotationIndex] = $newQuotation;
    }

    return $quotations;
}

/**
 * Identifica qual é o pacote original da cotação baseado nos rate_id atuais
 * 
 * @param array $quotation A cotação original
 * @return string|null Nome do pacote original ou null se não encontrado
 */
function identifyOriginalPackage($quotation)
{
    if (!isset($quotation['line_items']) || !is_array($quotation['line_items'])) {
        return null;
    }

    $packageCounts = [];

    foreach ($quotation['line_items'] as $item) {
        $currentRateId = $item['rate_id'] ?? null;

        if ($currentRateId && isset($item['ratesObject']) && is_array($item['ratesObject'])) {
            foreach ($item['ratesObject'] as $rate) {
                if (isset($rate['id']) && $rate['id'] == $currentRateId && isset($rate['package'])) {
                    $packageName = $rate['package'];
                    $packageCounts[$packageName] = ($packageCounts[$packageName] ?? 0) + 1;
                    break;
                }
            }
        }
    }

    // Retornar o pacote mais comum (em caso de empate, retorna o primeiro)
    if (!empty($packageCounts)) {
        arsort($packageCounts);
        return array_key_first($packageCounts);
    }

    return null;
}

/**
 * Recalcula os premiosGlobais exatamente como na função calculateOtherPremiunDataGeral()
 */
function recalculatePremiosGlobais($quotation)
{
    // Primeiro, calcular o prêmio simples (soma de todos os coverage_premium)
    $premioSimples = 0;
    foreach ($quotation['line_items'] as $item) {
        $premioSimples += $item['coverage_premium'] ?? 0;
    }

    // Obter a estrutura de premiosGlobais original ou criar nova
    $premiosGlobais = $quotation['premiosGlobais'] ?? [];

    // Garantir que todos os campos necessários existam
    $requiredFields = [
        'premioSimples',
        'premioEncargos',
        'premioComercial',
        'iva',
        'premioTotalAnual',
        'premioTotalSemestre',
        'premioTotalTrimestre',
        'premioTotalMensal',
        'premioFundoGarantia',
        'comercialMaisIva'
    ];

    foreach ($requiredFields as $field) {
        if (!isset($premiosGlobais[$field])) {
            $premiosGlobais[$field] = ['value' => 0, 'status' => true];
        }
    }

    // Aplicar os cálculos exatamente como na função TypeScript
    $premiosGlobais['premioSimples']['value'] = round($premioSimples, 2);
    $premiosGlobais['premioEncargos']['value'] = round($premioSimples * 0.2, 2);
    $premiosGlobais['premioComercial']['value'] = round(
        $premiosGlobais['premioSimples']['value'] + $premiosGlobais['premioEncargos']['value'],
        2
    );
    $premiosGlobais['iva']['value'] = round($premiosGlobais['premioComercial']['value'] * 0.14, 2);
    $premiosGlobais['premioTotalAnual']['value'] = round(
        $premiosGlobais['premioSimples']['value'] +
        $premiosGlobais['premioEncargos']['value'] +
        $premiosGlobais['iva']['value'],
        2
    );
    $premiosGlobais['premioTotalSemestre']['value'] = round($premiosGlobais['premioTotalAnual']['value'] / 2, 2);
    $premiosGlobais['premioTotalTrimestre']['value'] = round($premiosGlobais['premioTotalAnual']['value'] / 4, 2);
    $premiosGlobais['premioTotalMensal']['value'] = round($premiosGlobais['premioTotalAnual']['value'] / 12, 2);
    $premiosGlobais['premioFundoGarantia']['value'] = round($premioSimples * 0.05, 2);
    $premiosGlobais['comercialMaisIva']['value'] = round(
        $premiosGlobais['premioComercial']['value'] + $premiosGlobais['iva']['value'],
        2
    );
    $premiosGlobais['comercialMaisIva']['status'] = false;

    return $premiosGlobais;
}
function ucsentences($string)
{
    // Lista de palavras que NÃO devem ser capitalizadas (exceto no início da frase)
    $lowercase_words = [
        // Artigos
        'o',
        'a',
        'os',
        'as',
        'um',
        'uma',
        'uns',
        'umas',

        // Preposições
        'de',
        'da',
        'do',
        'das',
        'dos',
        'em',
        'por',
        'com',
        'sem',
        'para',
        'perante',
        'entre',
        'sob',
        'sobre',
        'até',
        'após',
        'ante',
        'desde',
        'contra',
        'trás',
        'per',

        // Conjunções coordenativas
        'e',
        'ou',
        'mas',
        'porém',
        'todavia',
        'contudo',
        'entretanto',
        'no entanto',
        'logo',
        'portanto',
        'pois',
        'porque',
        'que',
        'assim',
        'então',

        // Conjunções subordinativas
        'se',
        'quando',
        'como',
        'onde',
        'onde',
        'que',
        'quem',
        'qual',
        'quais',
        'cujo',
        'cuja',
        'cujos',
        'cujas',

        // Outras palavras que geralmente ficam em minúsculo
        'num',
        'numa',
        'dum',
        'duma',
        'noutro',
        'noutra',
        'doutro',
        'doutra',
        'num',
        'numa',
        'noutro',
        'noutra',
        'deste',
        'desta',
        'destes',
        'destas',
        'desse',
        'dessa',
        'desses',
        'dessas',
        'daquele',
        'daquela',
        'daqueles',
        'daquelas',
        'à',
        'ao',
        'aos',
        'às',
        'na',
        'no',
        'nas',
        'nos',
        'pelo',
        'pela',
        'pelos',
        'pelas'
    ];

    // Primeiro, dividir a string em frases
    $parts = preg_split('/([^\.\!\?;]+[\.\!\?;"]+)/', strtolower($string), (-1), PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

    $r = '';
    foreach ($parts as $sentence) {
        $sentence = trim($sentence);
        if (empty($sentence)) {
            continue;
        }

        // Capitalizar a primeira letra da frase
        $sentence = ucfirst($sentence);

        // Dividir a frase em palavras
        $words = preg_split('/(\s+)/', $sentence, -1, PREG_SPLIT_DELIM_CAPTURE);
        $capitalized_words = [];

        foreach ($words as $index => $word) {
            // Se for apenas espaços, manter como está
            if (preg_match('/^\s+$/', $word)) {
                $capitalized_words[] = $word;
                continue;
            }

            // Verificar se é a primeira palavra da frase ou após pontuação
            $is_first_word = ($index === 0 || preg_match('/^[\.\!\?;"]+$/', $words[$index - 1] ?? ''));

            // Verificar se a palavra está na lista de minúsculas
            $clean_word = preg_replace('/[^\p{L}]/u', '', $word);
            $clean_word_lower = mb_strtolower($clean_word, 'UTF-8');

            if (!$is_first_word && in_array($clean_word_lower, $lowercase_words)) {
                // Manter em minúsculo (mas preservar pontuação)
                $capitalized_words[] = mb_strtolower($word, 'UTF-8');
            } else {
                // Capitalizar a palavra
                $capitalized_words[] = mb_convert_case($word, MB_CASE_TITLE, 'UTF-8');
            }
        }

        $r .= implode('', $capitalized_words) . ' ';
    }

    // Corrigir o pronome "I" em inglês
    $r = preg_replace('/\bi\b/u', 'I', $r);

    // Capitalizar a primeira letra após aspas
    $r = preg_replace_callback('/("[a-z])/u', function ($m) {
        return strtoupper($m[0]);
    }, $r);

    // Corrigir casos especiais como "d'Ávila", "O'Brien"
    $r = preg_replace_callback("/(\b\w+)'([a-z])/iu", function ($m) {
        return $m[1] . "'" . mb_strtoupper($m[2], 'UTF-8');
    }, $r);

    // Capitalizar nomes próprios com prefixos (Mc, Mac, etc.)
    $r = preg_replace_callback('/\b(mc|mac|o\')([a-z])/iu', function ($m) {
        return $m[1] . mb_strtoupper($m[2], 'UTF-8');
    }, $r);

    // Corrigir siglas que foram erroneamente capitalizadas (como "EUA" que viraria "Eua")
    $siglas = ['EUA', 'ONU', 'OMS', 'PT', 'BR', 'EU', 'UK', 'USA', 'KZ', 'USD', 'EUR', 'AOA'];
    foreach ($siglas as $sigla) {
        $pattern = '/\b' . preg_quote(mb_strtolower($sigla, 'UTF-8'), '/') . '\b/u';
        $r = preg_replace($pattern, $sigla, $r);
    }

    return rtrim($r);
}

// Função alternativa mais robusta com mais opções
function smartUcSentences($string, $options = [])
{
    // Opções padrão
    $defaults = [
        'preserve_case' => true, // Preservar maiúsculas em siglas, etc.
        'locale' => 'pt', // 'pt', 'en', 'es'
        'exceptions' => [], // Exceções adicionais
    ];

    $options = array_merge($defaults, $options);

    // Dicionários por idioma
    $lowercase_dict = [
        'pt' => [
            'o',
            'a',
            'os',
            'as',
            'um',
            'uma',
            'uns',
            'umas',
            'de',
            'da',
            'do',
            'das',
            'dos',
            'em',
            'por',
            'com',
            'sem',
            'para',
            'perante',
            'entre',
            'sob',
            'sobre',
            'até',
            'após',
            'ante',
            'desde',
            'contra',
            'trás',
            'per',
            'e',
            'ou',
            'mas',
            'à',
            'ao',
            'aos',
            'às',
            'na',
            'no',
            'nas',
            'nos'
        ],
        'en' => [
            'a',
            'an',
            'the',
            'and',
            'or',
            'but',
            'nor',
            'for',
            'so',
            'yet',
            'at',
            'by',
            'in',
            'of',
            'on',
            'to',
            'up',
            'as',
            'is',
            'it'
        ],
        'es' => [
            'el',
            'la',
            'los',
            'las',
            'un',
            'una',
            'unos',
            'unas',
            'de',
            'del',
            'y',
            'o',
            'en',
            'por',
            'para',
            'con',
            'sin'
        ]
    ];

    // Usar dicionário do idioma especificado
    $lowercase_words = isset($lowercase_dict[$options['locale']])
        ? $lowercase_dict[$options['locale']]
        : $lowercase_dict['pt'];

    // Adicionar exceções personalizadas
    $lowercase_words = array_merge($lowercase_words, $options['exceptions']);

    // Converter para minúsculas primeiro
    $string_lower = mb_strtolower($string, 'UTF-8');

    // Dividir em frases
    $sentences = preg_split('/([.!?;]+[\s]*)/u', $string_lower, -1, PREG_SPLIT_DELIM_CAPTURE);

    $result = '';

    for ($i = 0; $i < count($sentences); $i += 2) {
        $sentence = trim($sentences[$i] ?? '');
        $punctuation = $sentences[$i + 1] ?? '';

        if (empty($sentence)) {
            $result .= $punctuation;
            continue;
        }

        // Dividir a frase em palavras
        $words = preg_split('/(\s+)/u', $sentence, -1, PREG_SPLIT_DELIM_CAPTURE);
        $processed_words = [];

        foreach ($words as $index => $word) {
            // Se for apenas espaços
            if (preg_match('/^\s+$/u', $word)) {
                $processed_words[] = $word;
                continue;
            }

            // Verificar se é a primeira palavra "real" da frase
            $is_first_real_word = true;
            for ($j = 0; $j < $index; $j++) {
                if (!preg_match('/^\s+$/u', $words[$j])) {
                    $is_first_real_word = false;
                    break;
                }
            }

            // Limpar a palavra (remover pontuação)
            $clean_word = preg_replace('/[^\p{L}\p{N}]/u', '', $word);
            $clean_word_lower = mb_strtolower($clean_word, 'UTF-8');

            // Verificar se deve ficar em minúsculo
            if (!$is_first_real_word && in_array($clean_word_lower, $lowercase_words)) {
                $processed_words[] = mb_strtolower($word, 'UTF-8');
            } else {
                // Capitalizar a palavra
                $processed_words[] = mb_convert_case($word, MB_CASE_TITLE, 'UTF-8');
            }
        }

        // Juntar as palavras processadas
        $capitalized_sentence = implode('', $processed_words);

        // Garantir que a primeira letra da frase seja maiúscula
        if (!empty($capitalized_sentence)) {
            $first_char = mb_substr($capitalized_sentence, 0, 1);
            $rest = mb_substr($capitalized_sentence, 1);
            $capitalized_sentence = mb_strtoupper($first_char, 'UTF-8') . $rest;
        }

        $result .= $capitalized_sentence . $punctuation;
    }

    // Correções finais
    $result = preg_replace('/\bi\b/u', 'I', $result); // "I" em inglês

    // Capitalizar após aspas
    $result = preg_replace_callback('/(["\'])\s*([a-z])/u', function ($m) {
        return $m[1] . ' ' . mb_strtoupper($m[2], 'UTF-8');
    }, $result);

    // Se preserve_case for true, restaurar siglas conhecidas
    if ($options['preserve_case']) {
        $common_acronyms = [
            'EUA',
            'ONU',
            'OMS',
            'PT',
            'BR',
            'EU',
            'UK',
            'USA',
            'KZ',
            'USD',
            'EUR',
            'AOA',
            'HTML',
            'CSS',
            'PHP',
            'SQL'
        ];

        foreach ($common_acronyms as $acronym) {
            $pattern = '/\b' . preg_quote(mb_strtolower($acronym, 'UTF-8'), '/') . '\b/u';
            $result = preg_replace($pattern, $acronym, $result);
        }
    }

    return trim($result);
}



// if (!function_exists('sf_date_ref')) {
//     /**
//      * Formata uma data para exibição em PDFs de proposta.
//      * Devolve string vazia se a data for null/inválida.
//      */
//     function sf_date_ref($date, string $format = 'd/m/Y'): string
//     {
//         if (empty($date)) {
//             return '';
//         }

//         try {
//             if ($date instanceof \DateTimeInterface) {
//                 return $date->format($format);
//             }

//             return \Carbon\Carbon::parse($date)->format($format);
//         } catch (\Throwable $e) {
//             return '';
//         }
//     }
// }

// if (!function_exists('sf_checked_ref')) {
//     /**
//      * Devolve o nome do ficheiro de imagem (check/uncheck) consoante
//      * o valor actual coincida ou não com o valor esperado.
//      *
//      * Uso típico no Blade:
//      *   <img src="{{ public_path('img/cotacao/' . sf_checked_ref($proposal->tipo_proposta, 'novo')) }}">
//      */
//     function sf_checked_ref($value, $expected, string $checked = 'checked.png', string $unchecked = 'unchecked.png'): string
//     {
//         if ($value === null) {
//             return $unchecked;
//         }

//         // Comparação case-insensitive e trim, útil para dados vindos da BD
//         $a = strtolower(trim((string) $value));
//         $b = strtolower(trim((string) $expected));

//         return $a === $b ? $checked : $unchecked;
//     }
// }
