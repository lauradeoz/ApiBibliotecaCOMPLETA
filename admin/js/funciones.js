
const url = 'http://localhost/ApiBiblioteca/api/libros';

//crear evento para que se ejecute el código cuando haya terminado de cargarse el DOM
document.addEventListener('DOMContentLoaded', () => {


    //realizo la llamada a la api para conseguir los datos
    fetch(url)
        .then(response => response.json())
        .then(data => mostrarLibros(data))
        .catch(error => console.error('Error:', error));

    document.getElementById("crear").addEventListener('click', () =>{
        //si document.querySelector('form').style.display devuelve un valor vacio
        //estado toma el segundo plano
        const estado = document.querySelector('form').style.display || 'none';
        if(estado === 'none'){
        document.querySelector('form').style.display = 'block'
        document.getElementById("crear").textContent = 'Ocultar formulario'
        }else{
        document.querySelector('form').style.display = 'none'
        document.getElementById("crear").textContent = 'Crear nuevo libro'

        }
    })

    document.querySelector('form').addEventListener('submit', enviarDatosNuevoLibro)
})

function mostrarLibros(datos){

    const libros = datos.data;
    console.log(libros)

    if(datos.success && datos.count > 0){
        //mostrar las cabeceras de la tabla
      document.getElementById('tablaLibros').innerHTML =
    `<tr class="encabezado">` +
    Object.keys(libros[0]).map(clave => 
        `<td>${clave.toUpperCase()}</td>
        ${
            clave == 'resumen' ? '<td class="centrado" colspan="2">Acciones</td>' : ''
        }

        `
    ).join('')
    
    + "</tr>";

 
        //muestro los libros por pantalla
        document.getElementById('tablaLibros').innerHTML +=

        libros.map(libro => `
    <tr>
        <td>${libro.id}</td>
        <td>${libro.titulo}</td>
        <td>${libro.autor}</td>
        <td>${libro.genero}</td>
        <td>${libro.fecha_publicacion}</td>
        <td><img src="../img/img_peques/${libro.imagen}" alt="${libro.titulo}"></td>
        <td class="centrado">${(libro.disponible == 1) ? "Sí" : "No" }</td>
        <td class="centrado">${(libro.favorito == 1) ? "Sí" : "No" }</td>
        <td>${(libro.resumen !== null && libro.resumen.length > 0) ? libro.resumen.substring(0, 100)+"..." : ''}</td>
        <td><button onclick="editarLibro(${libro.id})">Editar</button></td>
        <td><button onclick="eliminarLibro(${libro.id}, '${libro.titulo}')" class="btn-delete">Eliminar</button></td>
    </tr>
`).join('')

    }else if(datos.count == 0){
        document.getElementById('tablaLibros').innerHTML = "<p>No hay libros</p>";
    }
}

function eliminarLibro(id, titulo){
    const confirma = confirm(`¿Seguro que quieres eliminar el libro: ${titulo}?`)

    if(!confirma){
        return
    }

    //el usuario ha confirmado que quiere eliminar el libro
    fetch(`${url}/${id}`,{
        method: 'DELETE'
    })
        .then(response => response.json())
        .then(data => libroEliminado(data))
        .catch(error => console.error('Error:', error));
}

function libroEliminado(data){
    if(data.success){
    fetch(url)
        .then(response => response.json())
        .then(data => mostrarLibros(data))
        .catch(error => console.error('Error:', error));

    }else{
        alert("Hubo un problema al eliminar el libro, y mas si es Harry Potter, eso no se elimina cariño")
    }
}

function editarLibro(id){

}


function enviarDatosNuevoLibro(e){
    e.preventDefault();

    const mensajesError = document.querySelectorAll('.error');

    const titulo = document.getElementById('titulo').value.trim();
    const autor = document.getElementById('autor').value.trim();
    const genero = document.getElementById('genero').value.trim();
    const fecha_publicacion = parseInt(document.getElementById('fecha_publicacion').value);
    const imagen = document.getElementById('imagen').files[0];
    const disponible = document.getElementById('disponible').checked;
    const favorito = document.getElementById('favorito').checked;
    const resumen = document.getElementById('resumen').value.trim();


    //limpiar mensajes de error previos
    mensajesError.forEach(elemento => elemento.textContent = '');

    let errores = false;

    //realizar las validaciones
    if(!titulo){
        document.getElementById('error-titulo').textContent = "El título es obligatorio";
        errores = true;
    }

    if(!autor){
        document.getElementById('error-autor').textContent = "El autor es obligatorio";
        errores = true;
    }

    const anioActual = new Date().getFullYear();
    if(isNaN(fecha_publicacion) || fecha_publicacion < 1000 || fecha_publicacion > anioActual +1){
        document.getElementById('error-publicacion').textContent = "La fecha de publicación debe ser un año válido (4 dígitos)";
        errores = true;
    }

    if(resumen.length  > 1000){
        document.getElementById('error-resumen').textContent = "El resumen no puede superar los 1000 caracteres";
        errores = true;
    }

    //comprobar el archivo de imagen
    if(imagen){
        const validacionImagen = validarImagen(imagen)
        if(!validacionImagen.esValido){
            document.getElementById('error-imagen').textContent = validacionImagen.mensaje
            errores = true
        }
    }

    if(errores) return //si hay errores no se envia el formulario

    //si estamos aqui los datos del formulario son válidos
    const datos = {
        titulo,
        autor,
        genero,
        fecha_publicacion
    }

}

function validarImagen(archivo){
    console.log('Arcivo tipo: ' , archivo.type)
    console.log('Tamaño del archivo: ' , archivo.size)
    //si no hay archivo pasa la validacion
    if(!archivo){
        return {
            esValido: true,
            mensaje: ""
        };
    }

    //validar el tipo de archivo
    const tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']
    if(!tiposPermitidos.includes(archivo.type)){
        return {
            esValido: true,
            mensaje: "Solo se permiten archivos de imagen (JPEG, JPG, PNG, GIF, WebP)"
            }
    }

    //validar el tamaño del archivo
    const tamanioMaximo = 1024 * 1024
    const tamanioMaximoMB = 1
    if(archivo.size > tamanioMaximo){
        return {
            esValido: false,
            mensaje: `La imagen no puede superar los ${tamanioMaximoMB} MB. Tamaño actual: ${(archivo.size / (1024 * 1024)). toFixed(2)}MB.` 
        }
    }

    //comprobar que el archivo tenga contenido
    const tamanioMinimo = 1024
    if(archivo.size < tamanioMinimo){
        return {
            esValido: false,
            mensaje: "El archivo de la imagen está vacío."
        }
    }
    return {
        esValido: true,
        mensaje: ''
    }


}