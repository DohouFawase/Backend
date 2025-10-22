<?php
namespace App\Http\Repositories\Property;
use App\Models\Equipment;



class EquipementRepository
{
    public function __construct(private Equipment $equipment) {
//    dd($equipment);

    }

    // ------------------------------------------
    // A. Enregistrement (MIS À JOUR pour la clarté)
    // ------------------------------------------

   // ------------------------------------------

    public function createEquipment(array $data)
    {
      $equipment = $this->equipment->create($data); 
       return $equipment;
    }



    public function getAllEquipments()
    {
        $getEquimentWithCategory = $this->equipment->with('category')->get();
     
        return $getEquimentWithCategory;
    }


  
  
}