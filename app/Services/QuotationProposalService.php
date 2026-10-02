<?php

namespace App\Services;

use App\Repositories\QuotationProposalRepository;
use App\Models\QUO\QUO_quotation_proposals;

class QuotationProposalService
{
    public function __construct(protected QuotationProposalRepository $repo) {}

    public function getAll(array $filters = [], int $perPage = 15)
    {
        return $this->repo->getAll($filters, $perPage);
    }

    public function getByVersion(int $versionId)
    {
        return $this->repo->getByVersion($versionId);
    }

    public function getById(int $id): ?QUO_quotation_proposals
    {
        return $this->repo->getById($id);
    }

    public function findByToken(string $token): ?QUO_quotation_proposals
    {
        return $this->repo->findByToken($token);
    }

    public function create(array $data): QUO_quotation_proposals
    {
        return $this->repo->create($data);
    }

    public function update(int $id, array $data): ?QUO_quotation_proposals
    {
        return $this->repo->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repo->delete($id);
    }
}
