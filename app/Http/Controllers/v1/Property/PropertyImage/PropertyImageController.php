<?php

namespace App\Http\Controllers\v1\Property\PropertyImage;

use App\Http\Controllers\Controller;
use App\Http\Repositories\Property\PropertyImageRepository;
use App\Http\Requests\PropertyImage\CreatePropertyImageFormRequest;
use App\Models\AdVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class PropertyImageController extends Controller
{
    //
    protected PropertyImageRepository $imageRepository;

    public function __construct(PropertyImageRepository $imageRepository)
    {
        $this->imageRepository = $imageRepository;
    }


    public function index ()  
    {
        return 'Hello word';
    }


 public function store(CreatePropertyImageFormRequest $request)
    {
        // 1. Validation (Effectuée par CreatePropertyImageFormRequest)
        
        // 2. Récupération de l'AdVersion (Garantie d'exister par la Rule::exists)
        $adVersion = AdVersion::findOrFail($request->input('ad_version_id'));
        
        try {
            // 3. Traitement via le Repository (avec les paramètres bruts de la requête)
            $imageResult = $this->imageRepository->saveImages(
               $adVersion,
                $request->input('photos'), // Récupère le tableau d'objets photos du JSON
                (int)$request->input('main_image_index')
            );

            // Utilisation de votre fonction helper api_response
            return api_response(true, $imageResult['message'], ['ad_version_id' => $adVersion->id, 'images' => $imageResult['ids']], 201);

        } catch (\Exception $e) {
            // Utilisation de votre fonction helper api_response
            Log::error("Erreur d'attachement d'images: " . $e->getMessage());
            return api_response(false, "Une erreur est survenue lors de l'attachement des images: " . $e->getMessage(), [], 500);
        }
    }
    }

