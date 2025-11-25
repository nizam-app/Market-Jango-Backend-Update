<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoleRequest extends FormRequest
{

    public function rules()
    {
        switch ($this->method()) {
            case 'POST':
                return [
                    'name' => "required|string|unique:roles,name",
                    'permissions' => 'sometimes|array',
                    'permissions.*' => 'exists:permissions,id',
                ];
                break;

            case 'PATCH':
            case 'PUT':
                return [
                    'name' => "required|string",
                    'permissions' => 'sometimes|array',
                    'permissions.*' => 'exists:permissions,id',
                ];
                break;
        }
    }
    public function messages()
    {
        return [
            'name.required' => 'Please Enter Role Name',
            'name.unique' => 'Role Name Already Exists',
        ];
    }
}
