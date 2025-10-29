<?php

namespace App\Http\Repositories\Property;




use App\Models\EquipmentCategory;


class EquipementCategorieRepository
{
    public function __construct(private EquipmentCategory $equipmentCategory) {}

    // ------------------------------------------
    // A. Enregistrement (MIS À JOUR pour la clarté)
    // ------------------------------------------
    public function CreateCategries(array $data)
    {
        $category =  $this->equipmentCategory->create($data);
        return $category;
    }




    public function GetAllCategories()
    {
        return $this->equipmentCategory->all();
    }


    // app/Http/Repositories/Property/EquipementCategorieRepository.php

    public function UpdateCategory(EquipmentCategory $equipmentCategory, array $data): EquipmentCategory
    {

        $equipmentCategory->update($data);

        return $equipmentCategory;
    }


    public function DeleteCategory(EquipmentCategory $equipmentCategory)
    {
        return $equipmentCategory->delete();
    }
}
