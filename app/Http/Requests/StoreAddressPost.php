<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressPost extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'consignee' => 'required|min:2|max:255'
        ];
    }

    public function messages()
    {
        return [
            'consignee.required' => '请填写收货人姓名',
            'consignee.min' => '收货人姓名格式不正确',
            'consignee.max' => '收货人姓名格式不正确',
        ];
    }
}
