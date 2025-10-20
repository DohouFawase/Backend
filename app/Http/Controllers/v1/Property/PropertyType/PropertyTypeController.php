<?php

namespace App\Http\Controllers\v1\Property\PropertyType;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Repositories\Property\PropertyTypeRepository;
use App\Http\Requests\PropertyType\CreatePropertyTypeFormRequest;
use App\Http\Requests\PropertyType\UpdatePropertyTypeFormRequest;
use App\Models\PropertyType;
class PropertyTypeController extends Controller
{
    //

       public function __construct(private PropertyTypeRepository $propertyTypeRepository)
    {
    }

    public function index()
    {
        try {
            // code...
            $getEquipementCategories = $this->propertyTypeRepository->getAllPropertype();

            return $getEquipementCategories;
        } catch (\Throwable $e) {
            // throw $th;
            return api_response(false, 500, 'Erreur serveur', $e->getMessage());
        }
    }

    public function store(CreatePropertyTypeFormRequest $request)

    {
        //
        try {
            // code...
            $data = $this->propertyTypeRepository->createPropertype($request->all());
            return api_response(true, 'Catégorie créée avec succès', 200);

        } catch (\Throwable $e) {
            // throw $th;
            return api_response(false, 'Erreur serveur lors de la création de la catégories', 500);

        }
    }

    public function update(UpdatePropertyTypeFormRequest $request, PropertyType $propertyType)
    {
        //
        // code...
        try {
            $updateCategory = $this->propertyTypeRepository->UpdateCategory($propertyType, $request->all());
            return api_response(true, 'Catégorie mise à jour avec succès', $updateCategory);
        } catch (\Throwable $e) {
            // throw $th;
            return api_response(false, 'Erreur serveur lors de la mise à jour de la catégories', 500);

        }
    }
   
}
