<?php

namespace App\DTOs;

use Illuminate\Http\Request;

readonly class TransactionFilterDTO
{
    public function __construct(
        public ?int $userId = null,
        public ?string $type = null,
        public ?string $status = null,
        public ?string $provider = null,
        public ?string $reference = null,
        public ?string $search = null,
        public ?string $category = null,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public int $perPage = 20,
        public int $page = 1,
    ) {}

    /**
     * Create a DTO from an HTTP request.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: $request->has('user_id') ? (int) $request->input('user_id') : null,
            type: $request->query('type'),
            status: $request->query('status'),
            provider: $request->query('provider'),
            reference: $request->query('reference'),
            search: $request->query('search'),
            category: $request->query('category'),
            startDate: $request->query('start_date') ?? $request->query('start'),
            endDate: $request->query('end_date') ?? $request->query('end'),
            perPage: min((int) ($request->query('per_page') ?? $request->query('limit') ?? 20), 100),
            page: (int) ($request->query('page') ?? 1),
        );
    }
}
