<?php
namespace App\Http\Repositories\Property;
use App\Models\PropertyType;

class PropertyTypeRepository
{
    public function __construct(private PropertyType $propertyType) {
//    dd($equipment);

    }

    // ------------------------------------------
    // A. Enregistrement (MIS À JOUR pour la clarté)
    // ------------------------------------------

   // ------------------------------------------

    public function createPropertype(array $data)
    {
      $cretepropertyType = $this->propertyType->create($data); 
       return $cretepropertyType;
    }



    public function getAllPropertype()
    {
        $getPropertype = $this->propertyType->get();
        return $getPropertype;
    }


  

     public function UpdateCategory(PropertyType $propertyType, array $data): PropertyType
    {

        $propertyType->update($data);

        return $propertyType;
    }

  
}