<?php

namespace App\Http\Controllers\v1\Property\PropertyType;

use App\Http\Controllers\Controller;
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
            $getProperties = $this->propertyTypeRepository->getAllPropertype();
            if($getProperties->isEmpty()){
                return api_response(false, 'Aucun type de propriéter trouvé', 404);
            }
            return $getProperties;
        } catch (\Throwable $e) {
            // throw $th;
            return api_response(false, 500, 'Erreur serveur', $e->getMessage());
        }
    }

    public function store(CreatePropertyTypeFormRequest $request)

    {
        //
        $data = $this->propertyTypeRepository->createPropertype($request->all());
        return $data;
        // return api_response(true, 'Le type de propriéter a ete  créée avec succès', 201, $data);
        try {
            // code...

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
            return api_response(true, 'Le Type d propriéte a été mise à jour avec succès', 201, $updateCategory);
        } catch (\Throwable $e) {
            // throw $th;
            return api_response(false, 'Erreur serveur lors de la mise à jour de la catégories', 500);

        }
    }
   
}
