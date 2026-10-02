<?php

namespace App\Services\ENT;

use App\Models\ENT\ENT_sales_channels;
use App\Services\ServiceBase;
use Illuminate\Support\Str;

class ENTSalesChannelsService extends ServiceBase
{
public function getAll(array $f = []): array
{
    try {
        $q = ENT_sales_channels::query();
        
        if (isset($f['active'])) {
            $q->where('active', filter_var($f['active'], FILTER_VALIDATE_BOOLEAN));
        }
        
        if (isset($f['channel_type'])) {
            $q->where('channel_type', $f['channel_type']);
        }
        
        if (isset($f['responsible'])) {
            $q->where('responsible', 'like', "%{$f['responsible']}%");
        }
        
        if (isset($f['search'])) {
            $q->where(function($query) use ($f) {
                $query->where('name', 'like', "%{$f['search']}%")
                      ->orWhere('description', 'like', "%{$f['search']}%")
                      ->orWhere('responsible', 'like', "%{$f['search']}%");
            });
        }
        
        $data = $q->orderBy('name')->get();
        
        return $this->successResponse($data, 'Canais recuperados com sucesso.');
        
    } catch (\Exception $e) {
        return $this->errorResponse('Erro ao recuperar canais: ' . $e->getMessage(), 500);
    }
}
    public function getById(int $id): array
    {
        try {
            // Removido o with('parent', 'children') que não existe
            $r = ENT_sales_channels::find($id);
            
            if (!$r) {
                return $this->errorResponse('Canal não encontrado.', 404);
            }
            
            return $this->successResponse($r, 'Canal recuperado com sucesso.');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao recuperar canal: ' . $e->getMessage(), 500);
        }
    }

    public function create(array $data): array
    {
        try {
            // Gerar slug único baseado no nome
            $data['slug'] = $this->generateUniqueSlug($data['name']);
            
            // Garantir que active seja booleano
            $data['active'] = $data['active'] ?? true;
            
            $channel = ENT_sales_channels::create($data);
            
            return $this->successResponse(
                $channel, 
                'Canal criado com sucesso.', 
                201
            );
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao criar canal: ' . $e->getMessage(), 500);
        }
    }

    public function update(int $id, array $data): array
    {
        try {
            $r = ENT_sales_channels::find($id);
            
            if (!$r) {
                return $this->errorResponse('Canal não encontrado.', 404);
            }
            
            // Atualizar slug apenas se o nome mudou
            if (isset($data['name']) && $data['name'] !== $r->name) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $id);
            }
            
            $r->update($data);
            
            return $this->successResponse(
                $r->fresh(), 
                'Canal atualizado com sucesso.'
            );
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao atualizar canal: ' . $e->getMessage(), 500);
        }
    }

    public function delete(int $id): array
    {
        try {
            $r = ENT_sales_channels::find($id);
            
            if (!$r) {
                return $this->errorResponse('Canal não encontrado.', 404);
            }
            
            $r->delete();
            
            return $this->successResponse(null, 'Canal eliminado com sucesso.');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao eliminar canal: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get sales channels by type
     */
    public function getByType(string $type): array
    {
        try {
            $channels = ENT_sales_channels::where('channel_type', $type)
                ->where('active', true)
                ->orderBy('name')
                ->get();
            
            return $this->successResponse($channels, 'Canais recuperados com sucesso.');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao recuperar canais: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Toggle channel active status
     */
    public function toggleStatus(int $id): array
    {
        try {
            $channel = ENT_sales_channels::find($id);
            
            if (!$channel) {
                return $this->errorResponse('Canal não encontrado.', 404);
            }
            
            $channel->active = !$channel->active;
            $channel->save();
            
            $status = $channel->active ? 'ativado' : 'desativado';
            
            return $this->successResponse(
                $channel, 
                "Canal {$status} com sucesso."
            );
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao alterar status do canal: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get active channels list for dropdown
     */
    public function getActiveList(): array
    {
        try {
            $channels = ENT_sales_channels::where('active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'channel_type', 'responsible']);
            
            return $this->successResponse($channels, 'Lista recuperada com sucesso.');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao recuperar lista: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Generate unique slug
     */
    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }

    /**
     * Check if slug exists
     */
    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $query = ENT_sales_channels::where('slug', $slug);
        
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}