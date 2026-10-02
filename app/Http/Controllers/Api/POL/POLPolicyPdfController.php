<?php

namespace App\Http\Controllers\Api\POL;

use App\Http\Controllers\Controller;
use App\Models\POL\POL_policies;
use App\Models\POL\POL_policy_versions;
use Illuminate\Http\Request;

class POLPolicyPdfController extends Controller
{
    public function print(Request $request, int $id)
    {
        $policy = POL_policies::with([
            'customer',
            'branch',
            'agent',
            'versions.objectRisks.object.vehicle',
            'versions.objectRisks.object.person',
            'versions.objectRisks.object.property',
            'versions.objectRisks.coverage',
            'versions.specialConditions',
        ])->find($id);

        if (!$policy) {
            return response()->json([
                'success' => false,
                'message' => 'Apólice não encontrada.',
            ], 404);
        }

        $versionParam = $request->query('version_id');
        $selectedVersion = null;

        if ($versionParam) {
            $selectedVersion = POL_policy_versions::where('policy_id', $policy->id)
                ->where('id', (int) $versionParam)
                ->first();
        }

        if (!$selectedVersion) {
            $selectedVersion = $policy->versions
                ->sortByDesc('version')
                ->firstWhere('status', 'Active') ?? $policy->versions->sortByDesc('version')->first();
        }

        if (!$selectedVersion) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhuma versão de apólice encontrada.',
            ], 422);
        }

        $html = view('pdf.policy.index', [
            'policy' => $policy,
            'version' => $selectedVersion,
        ])->render();

        $cssPath = public_path('css/pdf/policy-generate/estilo.css');
        $css = file_exists($cssPath) ? file_get_contents($cssPath) : '';

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 18,
            'margin_bottom' => 14,
            'margin_left' => 12,
            'margin_right' => 12,
            'default_font_size' => 10,
            'default_font' => 'arial',
            'tempDir' => storage_path('app/mpdf'),
        ]);

        $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
        $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

        $fileName = 'policy_' . preg_replace('/[^A-Za-z0-9\-]/', '_', $policy->policy_number) . '_v' . $selectedVersion->version . '.pdf';

        return response(
            $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            ]
        );
    }
}

