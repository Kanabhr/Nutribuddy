<?php

namespace App\Http\Requests\Admin\Ecommerce;

class UpdateProductProblemSolutionRequest extends ProductMutationRequest
{
    public function rules(): array
    {
        return $this->problemSolutionRules();
    }
}
