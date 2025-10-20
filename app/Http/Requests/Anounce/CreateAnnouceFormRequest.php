<?php

namespace App\Http\Requests\Anounce;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
class CreateAnnouceFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
        'ad_id'=>["","","",""],
        'status'=>["","","",""],
        'validated_at'=>["","","",""],
        'validated_by_id'=>["","","",""],
        'ad_type'=>["","","",""],
        'property_type_id'=>["","","",""], 
        'seo_description'=>["","","",""],
        'full_address'=>["","","",""],
        'country'=>["","","",""],
        'department'=>["","","",""],
        'city'=>["","","",""],
        'district'=>["","","",""],
        'street'=>["","","",""],
        'additional_info'=>["","","",""],
        'longitude'=>["","","",""],
        'latitude'=>["","","",""],
        'area_value'=>["","","",""],
        'area_unit'=>["","","",""],
        'unit_count'=>["","","",""],
        'construction_type'=>["","","",""],
        'description=>["","","",""]',
        'price'=>["","","",""],
        'currency'=>["","","",""],
        'commission'=>["","","",""],
        'deposit_months'=>["","","",""],
        'periodicity'=>["","","",""],
        'is_negotiable=>["","","",""]',
        'photos_json'=>["","","",""],
        'main_photo_filenam'=> ["","","",""],
        'video_url'=>["","","",""],
        ];
    }




      public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors()
        ], 422));
    }
}
