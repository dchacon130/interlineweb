<?php
namespace App\Repositories;

use Core\{Auth, Log};
use App\Helpers\{ResponseHelper,AnexGridHelper};
use App\Models\{Devolucion};
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Intervention\Image\ImageManagerStatic as Image;

class DevolucionRepository {
    private $devolucion;

    public function __construct(){
        $this->devolucion = new Devolucion;
    }

    public function listar() : string {
        $anexgrid = new AnexGridHelper;

        try {
            $result = $this->devolucion->orderBy(
                $anexgrid->columna,
                $anexgrid->columna_orden
            )->skip($anexgrid->pagina)
             ->take($anexgrid->limite)
             ->get();

            return $anexgrid->responde(
                $result,
                $this->devolucion->count()
            );
        } catch (Exception $e) {
            Log::error(DevolucionRepository::class, $e->getMessage());
        }

        return "";
    }
    
    public function buscar(string $q) : array {
        $result = [];

        try {
            $result = $this->devolucion
                ->where('nombre', 'like', "%$q%")
                ->orderBy('nombre')
                ->get()
                ->toArray();
        } catch (Exception $e) {
            Log::error(DevolucionRepository::class, $e->getMessage());
        }

        return $result;
    }

    public function todo() : Collection {
        $result = null;

        try {
            $result = $this->devolucion->orderBy('id', 'DESC')
                           ->get();
        } catch (Exception $e) {
            Log::error(DevolucionRepository::class, $e->getMessage());
        }

        return $result;
    }

    public function guardar(devolucion $model, array $foto = null) : ResponseHelper {
        $rh = new ResponseHelper;

        try {
            $this->devolucion->id = $model->id;
            $this->devolucion->nombre = $model->nombre;
            $this->devolucion->marca = $model->marca;
            $this->devolucion->precio = $model->precio;
            $this->devolucion->costo = $model->costo;

            if(!empty($model->id)){
                $this->devolucion->exists = true;
            }

            if(!is_null($foto)){
                $nombre_archivo = sprintf(
                    'media/devolucion-%s.%s',
                    $model->id,
                    pathinfo($foto['name'], PATHINFO_EXTENSION)
                );

                $img = Image::make($foto['tmp_name']);
                $img->resize(500, 500);
                $img->save('public/' . $nombre_archivo);

                $this->devolucion->foto = $nombre_archivo;
            }

            $this->devolucion->save();
            $rh->setResponse(true);
        } catch (Exception $e) {
            Log::error(DevolucionRepository::class, $e->getMessage());
        }

        return $rh;
    }

    public function obtener($id) : devolucion {
        $devolucion = new devolucion;

        try {
            $devolucion = $this->devolucion->find($id);
        } catch (Exception $e) {
            Log::error(DevolucionRepository::class, $e->getMessage());
        }

        return $devolucion;
    }

    public function eliminar(int $id) : ResponseHelper {
        $rh = new ResponseHelper;

        try {
            $this->devolucion->destroy($id);
            $rh->setResponse(true);
        } catch (Exception $e) {
            Log::error(DevolucionRepository::class, $e->getMessage());
        }

        return $rh;
    }

    public function importar(array $archivo) : ResponseHelper {
        $rh = new ResponseHelper;

        try {
            $data = [];
            $fila = 0;
            if (($gestor = fopen($archivo['tmp_name'], "r")) !== FALSE) {
                while (($datos = fgetcsv($gestor, 1000, ";")) !== FALSE) {
                    if($fila > 0) {
                        $model = new devolucion;
                        // DEBEN VALIDAR ESTO
                        $model->nombre = $datos[0];
                        $model->marca  = $datos[1];
                        $model->costo  = $datos[2];
                        $model->precio = $datos[3];
                        $data[] = $model;
                    }

                    $fila++;
                }

                fclose($gestor);
            }

            foreach($data as $d) {
                $d->save();
            }

            $rh->setResponse(true);
        } catch (Exception $e) {
            Log::error(DevolucionRepository::class, $e->getMessage());
        }

        return $rh;
    }
}