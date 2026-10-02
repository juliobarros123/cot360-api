<?php

namespace App\Repositories;

use App\Models\QUO\QUO_quotation_proposals;

class QuotationProposalRepository
{
    public function getAll(array $filters = [], int $perPage = 15)
    {
        $query = QUO_quotation_proposals::with(['quotationVersion.quotation']);

        if (!empty($filters['quotation_version_id'])) {
            $query->where('quotation_version_id', $filters['quotation_version_id']);
        }

        if (!empty($filters['quotation_id'])) {
            $query->whereHas('quotationVersion', fn($q) => $q->where('quotation_id', $filters['quotation_id']));
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'expired') {
                $query->whereRaw('DATE_ADD(sent_at, INTERVAL 30 DAY) < NOW()');
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (!empty($filters['client_email'])) {
            $query->where('client_email', 'like', '%' . $filters['client_email'] . '%');
        }

        if (!empty($filters['client_name'])) {
            $query->where('client_name', 'like', '%' . $filters['client_name'] . '%');
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('client_name', 'like', '%' . $search . '%')
                    ->orWhere('client_email', 'like', '%' . $search . '%')
                    ->orWhere('tracking_token', 'like', '%' . $search . '%');
                if (is_numeric($search)) {
                    $q->orWhere('id', $search);
                }
            });
        }

        if (empty($filters['include_expired'])) {
            $query->where(function ($q) {
                $q->whereRaw('DATE_ADD(sent_at, INTERVAL 30 DAY) >= NOW()')
                  ->orWhereNull('sent_at');
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return ($perPage === 0)
            ? $query->get()
            : $query->paginate($perPage);
    }

    public function getByVersion(int $versionId)
    {
        return QUO_quotation_proposals::where('quotation_version_id', $versionId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getById(int $id): ?QUO_quotation_proposals
    {
        return QUO_quotation_proposals::find($id);
    }

    public function findByToken(string $token): ?QUO_quotation_proposals
    {
        return QUO_quotation_proposals::where('tracking_token', $token)->first();
    }

    public function create(array $data): QUO_quotation_proposals
    {
        return QUO_quotation_proposals::create($data);
    }

    public function update(int $id, array $data): ?QUO_quotation_proposals
    {
        $proposal = QUO_quotation_proposals::find($id);
        if (!$proposal) {
            return null;
        }
        $proposal->update($data);
        return $proposal;
    }

    public function delete(int $id): bool
    {
        $proposal = QUO_quotation_proposals::find($id);
        if (!$proposal) {
            return false;
        }
        return $proposal->delete();
    }
}
