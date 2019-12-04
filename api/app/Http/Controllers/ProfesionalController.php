<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Profesional; 
use App\TipoProfesional;
use App\Opinion;
use App\Helpers\JwtAuth;

class ProfesionalController extends Controller{

   public function insertProfesional(Request $request){
      $hash = $request->header('Authorization',null);
      $jwtAuth = new JwtAuth();
      $checkToken = $jwtAuth->checkToken($hash);

      if($checkToken){
         //Recoger POST
         $json = $request->input('json',null);
         $params = json_decode($json);
         $params_array = json_decode($json,true);

         //Validacion 
         $nombre=(!is_null($json) && isset($params->nombre)) ? $params->nombre : null;
         $tipo_profesional=(!is_null($json) && isset($params->tipo_profesional)) ? $params->tipo_profesional : null;
         $email=(!is_null($json) && isset($params->email)) ? $params->email : null;
         $telefono=(!is_null($json) && isset($params->telefono)) ? $params->telefono : null;
         

         //chequeamos que tengamos datos por POST
         if(!is_null($params_array)){

             //Reglas de validacion
            $validar= \Validator::make($params_array,[
               'nombre' => 'required',
               'tipo_profesional'=> 'required',
               'email'=> 'required|email',
               'telefono' => 'required'
            ]);

            //si hay errores envio el error
            if($validar->fails()){
               
               $data = array(
                  'status' => 'error',
                  'code' => 400,
                  'errores' => $this->errores($validar->errors()),
                  'messages' => 'Campos no validos',
               );

               return response()->json($data,200);  

            }

            // verificar que existe el tipo de profesional
            $tipo_profesional = TipoProfesional::find($tipo_profesional);
            if(is_null($tipo_profesional)){

               $data = array(
                  'status' => 'error',
                  'code' => 400,
                  'errores' => ['El tipo de profesional no existe'],
                  'messages' => 'No existe el tipo de profesional ingresado',
               );
               return response()->json($data,200);  
            }


            //subir imagen
            $imagen= $request->file('imagen',null);

            // verificar imagen
            if(!is_null($imagen)){
   
               $validar=\Validator::make(["imagen"=>$imagen],[
						'imagen' => 'mimes:jpeg,gif,png|required'
               ]);

               if($validar->fails()){
                  $data = array(
                     'status' => 'error',
                     'code' => 400,
                     'errores' => $this->errores($validar->errors()),
                     'messages' => 'Campos no validos',
                  );
                  return response()->json($data,200);    

               }else{
                  //Foto valida
                  $imagen_original_path = $imagen->getClientOriginalName();
                  $imagen_new_path = time().$imagen->hashName();
                  \Storage::disk('public')->put($imagen_new_path, \File::get($imagen));
                  
               }

            }else{
               $data = array(
                  'status' => 'error',
                  'code' => 400,
                  'messages' => 'Campos no validos',
                  'errores' => ['La imagen es obligatoria']
               );
               return response()->json($data,200);       
            }

            //obtener cv
            $cv=$request->file('cv',null);

            //VALIDAR CV
            if(!is_null($cv)){

               $validar=\Validator::make(["cv"=>$cv],[
                  'cv'=> 'required'
               ]);

               if($validar->fails()){
                  $data = array(
                     'status' => 'error',
                     'code' => 400,
                     'errores' => $this->errores($validar->errors()),
                     'messages' => 'Error CV',
                  );
                  return response()->json($data,200);         

               }else{
                  //CV valido
                  $cv_original_path=$cv->getClientOriginalName();
                  $cv_new_path=time().$cv->hashName();
                  \Storage::disk('public')->put($cv_new_path, \File::get($cv));
               }

            }else{
               $data = array(
                  'status' => 'error',
                  'code' => 400,
                  'messages' => 'Error CV',
                  'errores' => ['No ingresó el cv']
               );
               return response()->json($data,200);   
            }           
            
            //crear modelo
            $profesional=new Profesional;

            $profesional->nombre=$nombre;
            $profesional->id_tipo=$tipo_profesional->id;
            $profesional->email=$email;
            $profesional->telefono=$telefono;
            $profesional->imagen=$imagen_new_path;
            $profesional->cv=$cv_new_path;

            $profesional->save();
            $profesional->load('opiniones');

            $data = array(
               'status' => 'success',
               'profesional' => $profesional,
               'code' => 200,
               'message' => 'Se agregó el nuevo profesional'
            );
            return response()->json($data,200);           

         }else{
            $data = array(
               'status' => 'error',
               'code' => 400,
               'messages' => 'No hay datos por POST',
            );
            return response()->json($data,200); 
         }
        
      }else{
         $data = array(
            'status' => 'error',
            'code' => 400,
            'messages' => 'Fallo autentificacion',
         );
         return response()->json($data,200);         
      } 
   }

   public function getProfesional(){
      $profesionales= Profesional::orderBy('id','desc')->get()->load('tipo_profesional','opinion');
      return response()->json(array(
         'profesionales'=>$profesionales,
         'status'=>'success'
      ),200);
   }

   public function getProfesionalById($id){
      $profesional = Profesional::where([['id', '=', $id]])->get()->first();

      if(!is_null($profesional)){
         $profesional->load('tipo_profesional','opinion');
         return response()->json(array(
            'profesional'=>$profesional,
            'status'=>'success'
         ),200);

      }else{
         //El profesional con ese id no existe
         
			return response()->json(array(
				'message' => 'El profesional no existe.',
				'status' => 'error'
			), 200);
      }
   }

}
