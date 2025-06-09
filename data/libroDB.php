<?php


class LibroDB {


    private $db;
    private $table = 'libros';
    //recibe una conexion ($database) a una base de datos


    private $id;
    private $titulo;
    private $autor;
    private $genero;
    private $fecha_publicacion;
    private $disponible;


    public function __construct($database){
        $this->db = $database->getConexion();
    }


    public function getAll(){
        $sql = "SELECT * FROM {$this->table}";


        $resultado = $this->db->query($sql);


        if($resultado && $resultado->num_rows >0){
            $libros = [];
            //en
            while($row = $resultado->fetch_assoc()){
                $libros[] = $row;
            }
            return $libros;
        }else{
            //no hay datos, devolvemos un array vacio
        return[];
        }
    }
    public function getByID($id){
                $sql = "SELECT * FROM {$this->table} WHERE id = ?";
                $smtp = $this->db->prepare($sql);
                if($smtp){
                    //añado un parámetro a la consulta
                    //este va en el lugar de la ? en la variable $sql
                    //"i" es para asegurarnos de que el prámetro es un número entero
                    $smtp->bind_param("i", $id);
                    //ejecuta la consulta
                    $smtp->execute();
                    //lee el resultado de la consulta
                    $result = $smtp->get_result();
               
                    //comprueba si en el resiltado hay datos o está vacio
                if($result->num_rows > 0){
                    //devuelve un array asociativo con los datos
                    return $result->fetch_assoc();
                }
                //cierra
                $smtp->close();


                 }
                 //algo fallo
                 return null;
    }


    //crear un nuevo libro
    public function create($data){
        $sql = "INSERT INTO {$this->table} (titulo, autor, genero, fecha_publicacion, disponible, imagen, favorito, resumen) VALUES (?,?,?,?,?,?,?,?)";


        $stmt = $this->db->prepare($sql);
        if($stmt){
            //comprobar los datos opcionales
            $genero = isset($data['genero']) ? $data['genero'] : null;
            $fecha_publicacion = isset($data['fecha_publicacion']) ? $data['fecha_publicacion'] : null;
            $disponible = isset($data['disponible']) ? (int)(bool)$data['disponible'] : 1;
            $imagen = isset($data['imagen']) ? (int)(bool)$data['imagen'] : null;
            $favorito = isset($data['favorito']) ? (int)(bool)$data['favorito'] : 0;
            $resumen = isset($data['resumen']) ? (int)(bool)$data['resumen'] : "";

            $stmt->bind_param(
                //tipo de datos que tienen que tener cada parametro
                "sssiisis",
                $data['titulo'],
                $data['autor'],
                $genero,
                $fecha_publicacion,
                $disponible,
                $imagen,
                $favorito,
                $resumen,
            );


            if($stmt->execute()){
                //obtengo el id del libro que se acaba de crear
                $id = $this->db->insert_id;
                $stmt->close();
                //devuelve todos los datos del libro que acabamos de crear
                return $this->getByID($id);


            }
        }
        return false;


    }


    //eliminar un libro
    public function delete($id){
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if($stmt){
            $stmt->bind_param("i", $id);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        }
        return false;
    }


//actualizar libro (esta es crear la pregunta)
    public function update($id, $data){
        //todo actualizar libro
        $sql = "UPDATE {$this->table} SET
        titulo = ?,
        autor = ?,
        genero = ?,
        fecha_publicacion = ?,
        disponible = ?,
        imagen = ?,
        favorito = ?,
        resumen = ?
        WHERE id = ?
        ";


        //leer los datos actuales
        $libro = $this->getByID($id);
        if(!$libro){
            return false;


        }


        $stmt = $this->db->prepare($sql);
        if($stmt){


        $titulo = isset($data['titulo']) ? $data ['titulo'] : $libro['titulo'];
        $autor = isset($data['autor']) ? $data ['autor'] : $libro['autor'];
        $genero = isset($data['genero']) ? $data ['genero'] : $libro['genero'];
        $fecha_publicacion = isset($data['fecha_publicacion']) ? $data ['fecha_publicacion'] : $libro['fecha_publicacion'];
        $disponible = isset($data['disponible']) ? (int)(bool)$data ['disponible'] : $libro['disponible'];
        $imagen = isset($data['imagen']) ? $data ['imagen'] : $libro['imagen'];
        $favorito = isset($data['favorito']) ? $data ['favorito'] : $libro['favorito'];
        $resumen = isset($data['resumen']) ? $data ['resumen'] : $libro['resumen'];


            $stmt = $this->db->prepare($sql);
            if($stmt){
            $stmt->bind_param(
                "sssiisisi",
                $titulo,
                $autor,
                $genero,
                $fecha_publicacion,
                $disponible,
                $imagen,
                $favorito,
                $resumen,
                $id
            );
            }




            if($stmt->execute()){
                $stmt->close();
                //devuelve todos los datos del libro que acabamos de crear
                return $this->getById($id);
            }
            $stmt->close();
        }
        return false;
    }


}

