<?php

namespace App\Http\Controllers\v1\Property\Equipement;

use App\Http\Repositories\Property\EquipementRepository;
use App\Http\Requests\EquimentRequest\CreateEquimentFormRequest;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EquipementController extends Controller
{
    //
    public function __construct(private EquipementRepository $equipementRepository) {}

   public function index()
    {
        try {
            // code...
            $getEquipementCategories = $this->equipementRepository->getAllEquipments();
                if($getEquipementCategories->isEmpty()){
                    return api_response(false, 'Aucun équipement trouvé', 404);
                }
            return $getEquipementCategories;
        } catch (\Throwable $e) {
            // throw $th;
                      return api_response(false, 'Erreur serveur ', 500, $e->getMessage());

        }
    }


    public function store(CreateEquimentFormRequest $request)

    {
        //
        try {
            // code...
            $data = $this->equipementRepository->createEquipment($request->all());
            return api_response(true, 'Equipement créée avec succès', 200);
        } catch (\Throwable $e) {
            // throw $th;
            return api_response(false, 'Erreur: ' . $e->getMessage(), 500);
        }
    }
}
