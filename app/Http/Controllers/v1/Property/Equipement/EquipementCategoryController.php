<?php

namespace App\Http\Controllers\v1\Property\Equipement;

use App\Http\Controllers\Controller;
use App\Http\Repositories\Property\EquipementCategorieRepository;
use App\Http\Requests\EquimentRequest\CreateEquimentCategoryFormRequest;
use App\Http\Requests\EquimentRequest\UdapteEquimentCategoryFormRequest;
use App\Models\EquipmentCategory;

class EquipementCategoryController extends Controller
{
    //
    public function __construct(private EquipementCategorieRepository $equipementCategorieRepository)
    {
    }

    public function index()
    {
        try {
            // code...
            $getEquipementCategories = $this->equipementCategorieRepository->getAllCategories();

            return $getEquipementCategories;
        } catch (\Throwable $e) {
            // throw $th;
            return api_response(false, 500, 'Erreur serveur', $e->getMessage());
        }
    }

    public function store(CreateEquimentCategoryFormRequest $request)

    {
        //
        try {
            // code...
            $data = $this->equipementCategorieRepository->CreateCategries($request->all());
            return api_response(true, 'Catégorie créée avec succès', 200);

        } catch (\Throwable $e) {
            // throw $th;
            return api_response(false, 'Erreur serveur lors de la création de la catégories', 500);

        }
    }

    public function update(UdapteEquimentCategoryFormRequest $request, EquipmentCategory $equipmentscategory)
    {
        //
        // code...
        try {
            $updateCategory = $this->equipementCategorieRepository->UpdateCategory($equipmentscategory, $request->all());
            return api_response(true, 'Catégorie mise à jour avec succès', $updateCategory);
        } catch (\Throwable $e) {
            // throw $th;
            return api_response(false, 'Erreur serveur lors de la mise à jour de la catégories', 500);

        }
    }
   
}
