<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\DTOs\ElectricityVendDTO;
use App\Http\Requests\ElectricityVendRequest;
use App\Services\ElectricityService;
use App\Traits\ApiResponseTrait;

class ElectricityController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private ElectricityService $service) {}

    public function vend(ElectricityVendRequest $request)
    {
        $dto = new ElectricityVendDTO(
            $request->meter_number,
            $request->disco,
            $request->amount,
            $request->customer_name,
            $request->phone
        );

        $transaction = $this->service->vend($dto);
        return $this->success($transaction, 'Electricity vend initiated successfully.');
    }
}
