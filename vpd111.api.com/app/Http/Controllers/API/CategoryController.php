<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Categories;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Nette\Utils\Image;
use Illuminate\Support\Facades\Response;

class CategoryController extends Controller
{
    //
    function getAll() {
        $list = Categories::all();
        return response()->json($list, 200, ['Charset' => 'utf-8']);
    }

    function create(Request $request) {
        $input = $request->all();
        $image = $request->file("image");
        $manager = new ImageManager(new Driver());
        $imageName=uniqid().".webp";
        $folderName = "upload";
        $folderPath = public_path($folderName);
        if (!file_exists($folderPath) && !is_dir($folderPath))
            mkdir($folderPath, 0777);
        $sizes = [50, 150, 300, 600, 1200];
        foreach ($sizes as $size) {
            $imageSave = $manager->read($image);
            $imageSave->scale(width: $size);
            $imageSave->toWebp()->save($folderPath."/".$imageName);
        }
        $input["image"] = $imageName;
        $category = Categories::create($input);
        return response()->json($category, 200,["Charset"=>"utf-8"]);
    }

    function update(Request $request, $id)
    {
        $category = Categories::find($id);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        $input = $request->all();
        if ($request->hasFile('image')) {
            $image = $request->file("image");
            $manager = new ImageManager(new Driver());
            $folderName = "upload";
            $folderPath = public_path($folderName);
            if (!file_exists($folderPath) && !is_dir($folderPath)) {
                mkdir($folderPath, 0777);
            }
            $sizes = [50, 150, 300, 600, 1200];
            $imageName = uniqid() . ".webp";
            foreach ($sizes as $size) {
                $imageSave = $manager->make($image);
                $imageSave->resize($size, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $imageNameWithSize = $size . '_' . $imageName;
                $imageSave->encode('webp');
                $imageSave->save($folderPath . "/" . $imageNameWithSize);
            }
            $oldImagePath = $folderPath . "/" . $category->image;
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
            $input["image"] = $imageName;
        }
        $category->update($input);
        return response()->json($category, 200, ["Charset" => "utf-8"]);
    }

    public function delete($id)
    {
        $file =  Categories::findOrFail($id);
        $sizes = [50, 150, 300, 600, 1200];
        foreach ($sizes as $size) {
            $fileName = $_SERVER['DOCUMENT_ROOT'].'/upload/'.$size.'_'.$file["image"];
            if (is_file($fileName)) {
                unlink($fileName);
            }
        }
        $file->delete();
        return response()->json(['message' => 'category was successfully deleted']);
    }

    public function upload($filename)
    {
        $path = public_path('upload/' . $filename);

        if (!file_exists($path)) {
            abort(404);
        }

        $mimeType = mime_content_type($path);

        $response = Response::make(file_get_contents($path), 200);
        $response->header('Content-Type', $mimeType);

        return $response;
    }



}
