<?php
/**
 * Se encarga de interactuar con la base de datos con la tabla usuarios
 */

 require_once '../config/config.php';
 require_once 'enviarCorreos.php';

class UsuarioDB {

    private $db;
    private $table = 'usuarios';
    private $url = URL_ADMIN;
    
    //recibe una conexión ($database) a una base de datos y la mete en $db
    public function __construct($database){
        $this->db = $database->getConexion();
    }

    /**
     * Obtiene todos los usuarios
     */
    public function getAll(){
        $sql = "SELECT * FROM {$this->table}";
        $resultado = $this->db->query($sql);

        if($resultado && $resultado->num_rows > 0){
            $usuarios = [];
            while($row = $resultado->fetch_assoc()){
                $usuarios[] = $row;
            }
            return $usuarios;
        }
        return [];
    }

    /**
     * Obtiene un usuario por su ID
     */
    public function getById($id){
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if($stmt){
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();

            if($result->num_rows > 0){
                $usuario = $result->fetch_assoc();
                $stmt->close();
                return $usuario;
            }
            $stmt->close();
        }
        return null;
    }

    /**
     * Busca un usuario por su correo electrónico
     */
    public function getByEmail($correo){
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        
        if($stmt){
            $stmt->bind_param("s", $correo);
            $stmt->execute();
            $result = $stmt->get_result();

            if($result->num_rows > 0){
                $usuario = $result->fetch_assoc();
                $stmt->close();
                return $usuario;
            }
            $stmt->close();
        }
        return null;
    }

    /**
     * Crear un nuevo usuario
     */
    public function registrarUsuario($email, $password, $verificado = 0){
        $password = password_hash($password, PASSWORD_DEFAULT);
        $token = $this->generarToken();

        //comprobar si el email existe
        $existe = $this->correoExiste($email);

        $sql = "INSERT INTO usuarios (email, password, token, bloqueado) VALUES(?,?,?,?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sssi", $email, $password, $token, $verificado);

        if(!$existe){
            if($stmt->execute()){
                // correcto
                $mensaje = "Por favor, verifica tu cuenta haciendo clic en este enlace: $this->url/verificar.php?token=$token";
                $mensaje = Correo::enviarCorreo($email, "Cliente", "Verificación de cuenta", $mensaje);
                // $mensaje = $this->enviarCorreoSimulado($email, "Verificación de cuenta", $mensaje);
            }else{
                $mensaje = ["success" => false, "mensaje" => "Error en el registro: " . $stmt->error];
            }
        }else{
            $mensaje = ["success" => false, "mensaje" => "Ya existe una cuenta con ese email"];
        }

        return $mensaje;
    }

    /**
     * Actualizar datos de usuario
     */
    public function update($id, $data){
        $sql = "UPDATE {$this->table} SET email = ?, nombre = ?, apellido = ?";
        $params = [$data['correo'], $data['nombre'], $data['apellido']];
        $types = "sss";

        // Si se proporciona nueva contraseña, incluirla en la actualización
        if(isset($data['password']) && !empty($data['password'])){
            $sql .= ", password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            $types .= "s";
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;
        $types .= "i";

        $stmt = $this->db->prepare($sql);
        if($stmt){
            $stmt->bind_param($types, ...$params);
            
            if($stmt->execute()){
                $stmt->close();
                return $this->getById($id);
            }
            $stmt->close();
        }
        return false;
    }

    /**
     * Eliminar un usuario
     */
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

    /**
     * Verificar credenciales de login
     */
    public function verificarCredenciales($correo, $password){
        $usuario = $this->getByEmail($correo);

        // Si no existe el usuario
        if(!$usuario){
            return ['success' => false, 'mensaje' => 'Usuario no encontrado'];
        }

        // Verificar si el usuario está bloqueado
        if($usuario['bloqueado'] == 1){
            return ['success' => false, 'mensaje' => 'Usuario bloqueado'];
        }

        //comprobar que el usuario ha verificado el correo
        if($usuario['verificado'] === 0){
            return['success' => false, 'mensaje' => 'Verifica tu correo'];
        }

        // Verificar la contraseña
        if(!password_verify($password, $usuario['password'])){
            return ['success' => false, 'mensaje' => 'Contraseña incorrecta'];
        }

        // Credenciales correctas - actualizar último acceso
        $this->actualizarUltimoAcceso($usuario['id']);
        
        // No devolver la contraseña ni los tokens
        unset($usuario['password']);
        unset($usuario['token']);
        unset($usuario['token_recuperacion']);
        
        return ['success' => true, 'usuario' => $usuario, 'mensaje' => 'Login correcto'];        
    }

    /**
     * Actualizar el último acceso del usuario
     */
    public function actualizarUltimoAcceso($id){
        $sql = "UPDATE {$this->table} SET ultima_conexion = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if($stmt){
            $stmt->bind_param("i", $id);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        }
        return false;
    }

    /**
     * Bloquear/desbloquear usuario
     */
    public function cambiarEstadoBloqueado($id, $bloqueado = 1){
        $sql = "UPDATE {$this->table} SET bloqueado = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if($stmt){
            $stmt->bind_param("ii", $bloqueado, $id);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        }
        return false;
    }


    public function recuperarPassword($email){

        $existe = $this->correoExiste($email);

        $resultado = ["success" => false, "mensaje" => "El correo electrónico  proporcionado no corresponde a ningún usuario registrado."];

        //si el correo existe en la bbdd
        if($existe){
            $token = $this->generarToken();

            $sql = "UPDATE usuarios SET token_recuperacion = ? WHERE email = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ss", $token, $email);

            //ejecuta la consulta
            if($stmt->execute()){
                $mensaje = "Para restablecer tu contraseña, haz click en este enlace: $this->url/restablecer.php?token=$token";
                $mensaje = Correo::enviarCorreo($email, "Cliente", "Restablecer Contraseña", $mensaje);
                //$this->enviarCorreoSimulado($email, "Recuperación de contraseña", $mensaje);
                $resultado = ["success" => true, "mensaje" => "Se ha enviado un enlace de recuperación a tu correo"];
            }else{
                $resultado = ["success" => false, "mensaje" => "Error al procesar la solicitud"];
            }
        }
        return $resultado;
    }

    /**
     * Resetear contraseña usando token
     */
public function restablecerPassword($token, $nueva_password){
        $password = password_hash($nueva_password, PASSWORD_DEFAULT);
        //buscamos al usuario con el token proporcionado
        $sql = "SELECT id FROM usuarios WHERE token_recuperacion = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        $resultado = ["success" => false, "mensaje" => "El token de recuperación no es válido o ya  ha sido utilizado"];

        if($result->num_rows === 1){
            $row = $result->fetch_assoc();
            $user_id = $row['id'];

            //actualizar la contraseña y eliminar el token de recuperación
            $update_sql = "UPDATE usuarios SET password = ?, token_recuperacion = NULL WHERE id = ?";
            $update_stmt = $this->db->prepare($update_sql);
            $update_stmt->bind_param("si", $password, $user_id);

            if($update_stmt->execute()){
                $resultado = ["success" => true, "mensaje" => "Tu contraseña ha sido actualizada correctamente"];
            }else{
                $resultado = ["success" => false, "mensaje" => "Hubo  un error al actualizar tu contraseña. Por favor, intenta de nuevo más tarde"];
            }
        }
        return $resultado;
    }



    //verificar token

    public function verificarToken($token){
        //buscar al usuario con el token recibido
        $sql = "SELECT id FROM usuarios WHERE token = ? AND verificado = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows === 1){
            //token es válido actualizamos el estado de verificación del usuario
            $row = $result->fetch_assoc();
            $user_id = $row['id'];
            
            $update_sql = "UPDATE usuarios SET verificado = 1, token = NULL WHERE id= ?";
            $update_stmt = $this->db->prepare($update_sql);
            $update_stmt->bind_param("i", $user_id);

            $resultado = ["success" => false, "mensaje" => "Hubo un error al verificar tu cuenta. Por favor, intenta de nuevo más tarde"];

            if($update_stmt->execute()){
                $resultado = ["success" => true, "mensaje" => "Tu cuenta ha sido verificada. Ahora puedes iniciar sesión"];
            }
            
        }else{
            //no hay usuario con ese token y verificado = 0
            $resultado = ["success" => false, "mensaje" => "Token no válido"];
        }
        return $resultado;
    }    



    /**
     * Verificar si un correo ya existe
     */
    public function correoExiste($correo, $excludeId = null){
        $sql = "SELECT id FROM {$this->table} WHERE email = ?";
        $params = [$correo];
        $types = "s";

        if($excludeId){
            $sql .= " AND id != ?";
            $params[] = $excludeId;
            $types .= "i";
        }

        $stmt = $this->db->prepare($sql);
        if($stmt){
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $exists = $result->num_rows > 0;
            $stmt->close();
            return $exists;
        }
        return false;
    }

        //funcion para enviar correo simulado
    public function enviarCorreoSimulado($destinatario, $asunto, $mensaje){
        $archivo_log = __DIR__ . '/correos_simulados.log';
        $contenido = "Fecha: " . date('Y-m-d H:i:s'. "\n");
        $contenido .= "Para: $destinatario\n";
        $contenido .= "Asunto: $asunto\n";
        $contenido .= "Mensaje:\n$mensaje\n";
        $contenido .= "__________________________________________\n\n";

        file_put_contents($archivo_log, $contenido, FILE_APPEND);

        return ["success" => true, "mensaje" => "Registro exitoso. Por favor, verifica tu correo"];
    }

        //generar un token aleatorio
    public function generarToken(){
        return bin2hex(random_bytes(32));
    }
}