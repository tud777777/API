<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\FileRequest;
use App\Models\Coauthor;
use App\Models\File;


use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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
        $user = auth()->user();
        $access_coauthor = Coauthor::query()->where('user_id', '=', $user->id, 'and', 'file_id', '=', $request->file_id)->first();
        $access_author = File::query()->where('user_id', '=', $user->id, 'and', 'file_id', '=', $request->file_id)->first();
        if(!empty($access_coauthor) || !empty($access_author)){
            $file_name =File::query()->where('file_id', '=', $request->file_id)->first('file_name');
            $path = '/uploads/';
            Storage::move($path.$file_name['file_name'], $path.$request->name.substr($file_name['file_name'], strrpos($file_name['file_name'], '.')));
            File::query()->where('file_id', '=', $request->file_id)->update(['file_name' => $request->name.substr($file_name['file_name'], strrpos($file_name['file_name'], '.'))]);
            return response()->json([
                "success" => true,
                "message" => "Renamed"
            ]);
        }
        return throw new AccessDeniedHttpException;
    }

    public function delete(Request $request){
        $user = auth()->user();
        $access_coauthor = Coauthor::query()->where('user_id', '=', $user->id, 'and', 'file_id', '=', $request->file_id)->first();
        $access_author = File::query()->where('user_id', '=', $user->id, 'and', 'file_id', '=', $request->file_id)->first();
        if(!empty($access_coauthor) || !empty($access_author)) {
            $file_name = File::query()->where('file_id', '=', $request->file_id)->first('file_name');
            $path = '/uploads/';
            Storage::delete($path . $file_name['file_name']);
            File::query()->where('file_id', '=', $request->file_id)->delete();
            return response()->json([
                "success" => true,
                "message" => "File already deleted"
            ]);
        }
        return throw new AccessDeniedHttpException;
    }

    public function download(Request $request){
        $user = auth()->user();
        $access_coauthor = Coauthor::query()->where('user_id', '=', $user->id, 'and', 'file_id', '=', $request->file_id)->first();
        $access_author = File::query()->where('user_id', '=', $user->id, 'and', 'file_id', '=', $request->file_id)->first();
        if(!empty($access_coauthor) || !empty($access_author)) {
            $path = '/uploads/';
            $file_name = File::query()->where('file_id', '=', $request->file_id)->first('file_name');
            return Storage::download($path . $file_name['file_name']);
        }
        return throw new AccessDeniedHttpException;
    }

    public function access_add(Request $request)
    {
        $user = auth()->user();
        $file = File::query()->where('file_id', '=', $request->file_id)->first();
        $exists = Coauthor::query()->where('file_id', $file->id)->where('user_id', $user->id)->exists();
        $response = [];
        if($user->id == $file->user_id && empty($exists)){
            $user_add = User::query()->where('email', '=', $request->email)->first();
            $user_add->coauthor_user()->attach($file->id);
            $coauthors = Coauthor::query()->where('file_id','=', $file->id)->with('user')->get();
            $response[] = [
                "fullname" => $user->first_name.' '.$user->last_name,
                "email" => $user->email,
                "type" => "author"
            ];
            foreach ($coauthors as $coauthor){
                $response [] = [
                    'fullname'=> $coauthor->user[0]->first_name.' '.$coauthor->user[0]->last_name,
                    'email'=> $coauthor->user[0]->email,
                    'type'=> 'coauthor'
                ];
            }
            return response()->json($response,200);
        }
        return throw new AccessDeniedHttpException;
    }

    public function access_del(Request $request)
    {
        $user = auth()->user();
        $file = File::query()->where('file_id', '=', $request->file_id)->first();
        $exists = Coauthor::query()->where('file_id', $file->id)->where('user_id', $user->id)->exists();
        $response = [];
        if($user->id == $file->user_id && !empty($exists)){
            $user_add = User::query()->where('email', '=', $request->email)->first();
            $user_add->coauthor_user()->detach($file->id);
            $coauthors = Coauthor::query()->where('file_id','=', $file->id)->with('user')->get();
            $response[] = [
                "fullname" => $user->first_name.' '.$user->last_name,
                "email" => $user->email,
                "type" => "author"

            ];
            foreach ($coauthors as $coauthor){
                $response [] = [
                    'fullname'=> $coauthor->user[0]->first_name.' '.$coauthor->user[0]->last_name,
                    'email'=> $coauthor->user[0]->email,
                    'type'=> 'coauthor'
                ];
            }
            return response()->json($response,200);
        }
        return throw new AccessDeniedHttpException;
    }

    public function access_show(Request $request){
        $count = 0;
        $user = auth()->user();
        $files = File::query()->where('user_id','=', $user->id)->with('user')->get();
        $response = [];
        foreach($files as $file){
            $author = $file->user[0];
            $coauthors = Coauthor::query()->where('file_id','=', $file->id)->with('user')->get();
            $response[] = [
                'file_id' => $file->file_id,
                'name' => $file->file_name,
                'url' => substr($request->url(), 0,20) . '/' . $file->file_id,
                'accesses' => [
                    [
                        'fullname'=> $author->first_name.' '.$author->last_name,
                        'email'=> $author->email,
                        'type'=> 'author'
                    ],
                ]
            ];
            foreach ($coauthors as $coauthor){
                $response[$count]['accesses'] [] = [
                    'fullname'=> $coauthor->user[0]->first_name.' '.$coauthor->user[0]->last_name,
                    'email'=> $coauthor->user[0]->email,
                    'type'=> 'coauthor'
                ];
            }
            $count++;
        }
        return response()->json($response, 200);
    }

    public function access_user(Request $request){
        $user = auth()->user();
        $access_files = Coauthor::query()->where('user_id', '=', $user->id)->with('file')->get();
        $response = [];
        foreach($access_files as $access_file){
            $file = $access_file->file[0];
            $response [] = [
                'file_id' => $file->file_id,
                'name' => $file->file_name,
                'url' => substr($request->url(), 0,15) . '/files/' . $file->file_id,
            ];
        }
        return response()->json($response, 200);
    }
}
