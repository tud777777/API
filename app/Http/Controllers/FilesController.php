<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\FileRequest;
use App\Models\File;


use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FilesController extends Controller
{
    public function store(FileRequest $request){
        $user = auth()->user();
        $path = '/uploads/';
        $response = [];
        if(!$request->hasFile('files')) {
            return response()->json(['upload_file_not_found'], 400);
        }
        foreach($request->file('files') as $file) {
            $file_id = Str::random(10);
            $file_name = $file->getClientOriginalName();
            $file_name_name = substr($file_name,0, strrpos($file_name, '.'));
            $file_name_type = substr($file_name, strrpos($file_name, '.'));
            $check = File::query()->where('file_name', $file_name)->first();
            if (!empty($check)) {
                $i = 1;
                $check = File::query()->where('file_name', $file_name_name.' ('.$i.')'.$file_name_type)->first();
                while (!empty($check)){
                    $i++;
                    $check = File::query()->where('file_name', $file_name_name.' ('.$i.')'.$file_name_type)->first();
                }
                $user->file()->create(['file_id' => $file_id, 'file_name' => $file_name_name.' ('.$i.')'.$file_name_type]);
                Storage::putFileAs($path, $file, $file_name_name.' ('.$i.')'.$file_name_type);
                $response[] = [
                    'success' => true,
                    'message' => 'Success',
                    'name' => $file_name,
                    'url' => $request->url() . '/' . $file_id,
                    'file_id' => $file_id,
                ];
            }
            else{
                $user->file()->create(['file_id' => $file_id, 'file_name' => $file_name]);
                Storage::putFileAs($path, $file, $file_name);
                $response[] = [
                    'success' => true,
                    'message' => 'Success',
                    'name' => $file_name,
                    'url' => $request->url() . '/' . $file_id,
                    'file_id' => $file_id,
                ];

            }

        }
        return response()->json($response , 200);
    }

    public function edit(Request $request){
        $file_name =File::query()->where('file_id', '=', $request->file_id)->first('file_name');
        $path = '/uploads/';
        Storage::move($path.$file_name['file_name'], $path.$request->name.substr($file_name['file_name'], strrpos($file_name['file_name'], '.')));
        File::query()->where('file_id', '=', $request->file_id)->update(['file_name' => $request->name.substr($file_name['file_name'], strrpos($file_name['file_name'], '.'))]);
        return response()->json([
            "success" => true,
            "message" => "Renamed"
        ]);
    }

    public function delete(Request $request){
        $file_name =File::query()->where('file_id', '=', $request->file_id)->first('file_name');
        $path = '/uploads/';
        Storage::delete($path.$file_name['file_name']);
        File::query()->where('file_id', '=', $request->file_id)->delete();
        return response()->json([
            "success" => true,
            "message" => "File already deleted"
        ]);
    }

    public function download(Request $request){
        $path = '/uploads/';
        $file_name =File::query()->where('file_id', '=', $request->file_id)->first('file_name');
        return Storage::download($path.$file_name['file_name']);
    }

    public function access_add(Request $request)
    {
        $user = User::query()->where('email', '=', $request->email)->first();
        $user->coauthor_user();
        $file = File::query()->where('file_id', '=', $request->file_id)->first();
        $file->coauthor_file();
        return 1;
    }
}
