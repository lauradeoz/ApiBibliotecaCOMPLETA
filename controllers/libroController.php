<?php
//recibe los datos de una petición y devuelve una respuesta
class LibroController {
    private $libroDB;
    private $requestMethod;
    private $libroId;

    //el constructor recibe un objeto de la clase LibroDB
    //el método que se ha utilizado en la llamada: GET, POST, PUT o DELETE
    //un id de un libro que puede ser nulo
    public function __construct($db, $requestMethod, $libroId = null)
    {
        $this->libroDB = new LibroDB($db);
        $this->requestMethod = $requestMethod;
        $this->libroId = $libroId;
    }


    public function processRequest(){

        //comprobar si viene la clave _method en el objeto
        $metodo = $this->requestMethod;
        if($this->requestMethod  === 'POST' && isset($_POST['_method'])){
            $metodo = strtoupper($_POST['_method']);
        }

        //comprobar si la petición ha sido realizada con GET, POST, PUT, DELETE
        switch($metodo){
            case 'GET':
                if($this->libroId){
                    //devolver un libro
                    $respuesta = $this->getLibro($this->libroId);
                }else{
                    //libroId es nulo y devuleve todos los libros
                    $respuesta = $this->getAllLibros();
                }
                break;

            case 'POST':
                //crear un nuevo libro
                $respuesta = $this->createLibro();
                break;

                //actualizar un libro
            case 'PUT':
                $respuesta =$this->updateLibro($this->libroId);
                break;

                //borrar un libro
            case 'DELETE':
                $respuesta = $this->deleteLibro($this->libroId);
                break;
                
            default:
                $respuesta = $this->noEncontradoRespuesta();
                break;
        }

            header($respuesta['status_code_header']);
            if($respuesta['body']){
                echo $respuesta['body'];
            }
    }

    private function getAllLibros(){
        //conseguir todos los libros de la tabla libros
        $libros = $this->libroDB->getAll();

        //construir la respuesta
        $respuesta['status_code_header'] = 'HTTP/1.1 200 OK';
        $respuesta['body'] = json_encode([
            'success' => true,
            'data' => $libros,
            'count' => count($libros)
        ]);
        return $respuesta;
    }

    private function getLibro($id){
        //llamo a la función que devuelve un libro o null
        $libro = $this->libroDB->getById($id);
        //comprobar si $libro es null
        if(!$libro){
            return $this->noEncontradoRespuesta();
        }
        //hay libro
        //construir la respuesta
        $respuesta['status_code_header'] = 'HTTP/1.1 200 OK';
        $respuesta['body'] = json_encode([
            'success' => true,
            'data' => $libro
        ]);
        return $respuesta;
    }

    private function createLibro(){
        //verifica como vienen los datos: en el body(JSON) o en $_POST (formData)
        if(!empty($_POST['datos'])){
            //los datos vienen en formData y puede que venga un archivo
            $input = json_decode($_POST['datos'], true);
        }else{
            //datos vienen en el JSON en el body
            $input = json_decode(file_get_contents('php://input'), true);
        }
        

        if(!$this->validarDatos($input)){
           return $this->datosInvalidosRespuesta();
        }

        //comprobar si viene la imagen y procesarla
        $nombreImagen = "";
        //comprueba que viene un archivo 'imagen' y que no hay error al subir el archivo
        if(isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK){
            //validar imagen
            $validacionImagen = $this->validarImagen($_FILES['imagen']);
            if(!$validacionImagen['valida']){
                //la imagen no ha pasado la validacion
                return $this->imagenInvalidaRespuesta($validacionImagen['mensaje']);
            }

            //viene un archivo y es una imagen válida
            //guardar imagen en el servidor con nombre basado en el titulo
            $nombreImagen = $this->guardarImagen($_FILES['imagen'], $input['titulo']);
            if(!$nombreImagen){
                return $this->errorGuardarImagenRespuesta();
            }

        }//fin de comprobacion de si viene una imagen

        //añadir el nombre de la imagen a los datos del nuevo libro
        if($nombreImagen){
            $input['imagen'] = $nombreImagen;
        }

        $libro = $this->libroDB->create($input);

        if(!$libro){
            return $this->internalServerError();
        }

        //libro creado 
        //construir la respuesta
        $respuesta['status_code_header'] = 'HTTP/1.1 201 Created';
        $respuesta['body'] = json_encode([
            'success' => true,
            'data' => $libro,
            'message' => 'Libro creado con exito'
        ]);
        return $respuesta;

    }

    private function updateLibro($id){
        $libro = $this->libroDB->getByID($id);
        if(!$libro){
            return $this->noEncontradoRespuesta();
        }

        //el libro existe
        //verificar si los datos vienen en $_POST con FormData y el method spoofing o en el body
        if(!empty($_POST['datos'])){
            $input = json_decode($_POST['datos'], true);
        }else{
            //leo los datos que llegan a en el body de la petición
            $input = json_decode(file_get_contents('php://input'), true);
        }
        //validar datos
        if(!$this->validarDatos($input)){
            return $this->datosInvalidosRespuesta();
        }

        //el libro existe y los datos que llegan son válidos

        //guardar el nombre de la imagen actual
        $nombreImagenAnterior = $libro['imagen'];
        $nombreImagenNueva = $nombreImagenAnterior;

        //procesar la imagen si viene
        if(isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK){
            //se ha subido un archivo y se ha subido sin errores
            $validacionImagen = $this->validarImagen($_FILES['imagen']);
            if(!$validacionImagen['valida']){
                return $this->imagenInvalidaRespuesta($validacionImagen['mensaje']);
            }

            //guardamos la  nueva imagen con el nombre basado en el titulo
            $nombreImagenNueva = $this->guardarImagen($_FILES['imagen'], $input['titulo']);
            if(!$nombreImagenNueva){
                return $this->errorGuardarImagenRespuesta();
            }
        }

        $input['imagen'] = $nombreImagenNueva;

        $libroActualizado = $this->libroDB->update($this->libroId, $input);

        if(!$libroActualizado){
            return $this->internalServerError();
        }

        //el libro se ha actualizado con exito
        //construyo la respuesta
        $respuesta['status_code_header'] = 'HTTP/1.1 200 OK';
        $respuesta['body'] = json_encode([
            'success' => true,
            'message' => 'Libro actualizado exitosamente',
            'data' => $libroActualizado
        ]);
        return $respuesta;
    }

    private function deleteLibro($id){
        $libro = $this->libroDB->getById($id);

        if(!$libro){
            return $this->noEncontradoRespuesta();
        }

        if($this->libroDB->delete($id)){
            //libro borrado
            $this->eliminarImagen($libro['imagen']);
            //construir la respuesta
            $respuesta['status_code_header'] = 'HTTP/1.1 200 OK';
            $respuesta['body'] = json_encode([
                'success' => true,
                'message' => 'Libro eliminado'
            ]);
        return $respuesta;

        }else{
            return $this->internalServerError();
        }

    }//fin delete libro


    private function guardarImagen($archivo, $titulo){
        //limpiar el titulo para utilizarlo como nombre de archivo
        $nombreLimpio = $this->limpiarNombreArchivo($titulo);

        //obtener la extension del archivo
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        //crear el nombre del archivo
        $nombreArchivo = $nombreLimpio . "." . $extension;

        //definir rutas
        $directorioDestino = '../img/img_peques/';
        $rutaCompleta = $directorioDestino . $nombreArchivo;

        //crear el directorio si no exite 
        if(!file_exists($directorioDestino)){
            mkdir($directorioDestino, 0755, true);
            //son permisos del directorio en notacion octal, son todos los permisos, el propietario le da todos los permisos y al usuario solo el de lectura, no puedes modificar el archivo
            //mkdir crea directorios
            //con el 7 le estas dando todos los permisos (lectura, escritura y ejecución)
        }

        //movemos el archivo subido
        if(move_uploaded_file($archivo['tmp_name'], $rutaCompleta)){
            return $nombreArchivo;
        }

        return false;

    }

    private function eliminarImagen ($nombreArchivo){
        if(empty($nombreArchivo)) return;

        $rutaArchivo = "../img/img_peques/" . $nombreArchivo;
        if(file_exists($rutaArchivo)){
            unlink($rutaArchivo);
        }
    }

    private function validarDatos($datos){
        if(!isset($datos['titulo']) || !isset($datos['autor'])){
            return false;
        }
        //validar que la fecha sea un número de 4 dígitos, mayor a 1000 y menor que el año que viene
        
        $anio = $datos['fecha_publicacion'];
        $anioActual = (int)date("Y");

        if(!is_numeric($anio) || strlen((string)$anio) !== 4 || $anio < 1000 || $anio > $anioActual + 1){
            return false;
        }

        return true;
    }

    private function validarImagen($archivo){
        //validar que el archivo recibido sea una imagen válida
        if($archivo['error'] !== UPLOAD_ERR_OK){
            return['valida' => false, 'mensaje' => "Error al subor el archivo"];
        }

        //verificar el tamaño del archivo (1MB max)
        $tamanioMaximo =  1024 *1024;
        if($archivo['size'] > $tamanioMaximo){
            return['valida' => false, 'mensaje' => "La imagen no puede superar 1MB"];
        }

        //verificar tipo MINE 
        $tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if(!in_array($archivo['type'], $tiposPermitidos)){
            return ['valida' => false, 'mensaje' => "Solo se permiten imágenes JPEG, JPG, PNG, GIF, WebP"];
        }

        //verificar la extension del archivo
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $extensionPermitidas = ['jpeg', 'jpg', 'png', 'gif', 'webp'];
        if(!in_array($extension, $extensionPermitidas)){
            return ['valida' => false, 'mensaje' => "Extensión del archivo no permitida"];
        }

        //vetificar que realmente sea una imagen
        $infoImagen = getimagesize($archivo['tmp_name']);
        if($infoImagen === false){
            return ['valida' => false, 'mensaje' => "El archivo no es una imagen válida"];
        }  

        return ['valida' => true, 'mensaje' => ""];
    }

    private function noEncontradoRespuesta(){
        $respuesta['status_code_header'] = 'HTTP/1.1 404 Not Found';
        $respuesta['body'] = json_encode([
            'success' => false,
            'error' => 'Libro no encontrado'
        ]);
        return $respuesta;
    }

    private function datosInvalidosRespuesta(){
        $respuesta['status_code_header'] = 'HTTP/1.1 422 Unprocessable Entity';
        $respuesta['body'] = json_encode([
            'success' => false,
            'error' => 'Datos de entrada inválidos. Se requiere título y autor. La fecha tiene formato (YYYY)'
        ]);
        return $respuesta;
    }

    private function internalServerError(){
        $respuesta['status_code_header'] = 'HTTP/1.1 500 Internal Server Error';
        $respuesta['body'] = json_encode([
            'success' => false,
            'error' => 'Error interno del servidor'
        ]);
        return $respuesta;
    }

    private function imagenInvalidaRespuesta($mensaje){
        $respuesta['status_code_header'] = 'HTTP/1.1 422 Unprocessable Entity';
        $respuesta['body'] = json_encode([
            'success' => false,
            'error' => 'Imagen inválida' . $mensaje
        ]);
        return $respuesta;
    }

    private function errorGuardarImagenRespuesta(){
        $respuesta['status_code_header'] = 'HTTP/1.1 500 Internal Server Error ';
        $respuesta['body'] = json_encode([
            'success' => false,
            'error' => 'Error al guardar la imagen en el servidor' 
        ]);
        return $respuesta;
    }

    /**
     * Limpia el título para usarlo como nombre de archivo
     * @param string $titulo - Título del libro
     * @return string - Nombre limpio para archivo
     */
    private function limpiarNombreArchivo($titulo) {
        // Convertir a minúsculas
        $nombre = strtolower($titulo);
        
        // Reemplazar caracteres especiales y espacios
        $nombre = preg_replace('/[áàäâ]/u', 'a', $nombre);
        $nombre = preg_replace('/[éèëê]/u', 'e', $nombre);
        $nombre = preg_replace('/[íìïî]/u', 'i', $nombre);
        $nombre = preg_replace('/[óòöô]/u', 'o', $nombre);
        $nombre = preg_replace('/[úùüû]/u', 'u', $nombre);
        $nombre = preg_replace('/[ñ]/u', 'n', $nombre);
        $nombre = preg_replace('/[ç]/u', 'c', $nombre);
        
        // Reemplazar espacios y caracteres no alfanuméricos con guiones bajos
        $nombre = preg_replace('/[^a-z0-9]/i', '_', $nombre);
        
        // Eliminar guiones bajos múltiples
        $nombre = preg_replace('/_+/', '_', $nombre);
        
        // Eliminar guiones bajos al inicio y final
        $nombre = trim($nombre, '_');
        
        // Limitar longitud
        if (strlen($nombre) > 50) {
            $nombre = substr($nombre, 0, 50);
            $nombre = trim($nombre, '_');
        }
        
        return $nombre ?: 'libro_sin_titulo';
    }

}