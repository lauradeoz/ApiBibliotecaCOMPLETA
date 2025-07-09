// URL base para hacer las peticiones a la API de libros
const url = 'http://www.alumnalaura.com/api/index.php/libros';

let librosData = [] //almacena los datos de todos los libros
let modoEdicion = false //para saber si estamos creando o editando
let libroEditandoId = null //ID del libro que se está editando

// Ejecuta este código cuando el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', () => {

    // Realiza una solicitud GET a la API para obtener todos los libros
    // fetch(url)
    //     .then(response => response.json())
    //     .then(data => mostrarLibros(data)) // Muestra los libros en la tabla
    //     .catch(error => console.error('Error:', error)); // Muestra error si ocurre

    fetch(url)
    .then(response => response.text())
    .then(text => {
        // Limpiar la respuesta
        let jsonString = text.trim();
        
        // Si empieza con "Array{", remover "Array"
        if (jsonString.startsWith('Array{')) {
            jsonString = jsonString.substring(5);
        }
        
        // Parsear el JSON
        const data = JSON.parse(jsonString);
        mostrarLibros(data);
    })
    .catch(error => {
        console.error('Error:', error);
    });

    // Evento para mostrar u ocultar el formulario al hacer clic en "Crear nuevo libro"
    document.getElementById("crear").addEventListener('click', () => {
        const estado = document.querySelector('form').style.display || 'none';

        if (estado === 'none'){
            document.querySelector('form').style.display = 'block';
            document.getElementById("crear").textContent = 'Ocultar formulario';
        } else {
            document.querySelector('form').style.display = 'none';
            document.getElementById("crear").textContent = 'Crear nuevo libro';
        }
        if(modoEdicion){
            resetearModoCreacion()
        }

    })

    // Evento para enviar el formulario de nuevo libro
    document.querySelector('form').addEventListener('submit', enviarDatosNuevoLibro);
})

// Función para mostrar los libros en una tabla HTML
function mostrarLibros(datos){
    const libros = datos.data;
    librosData = libros;
    console.log(libros);

    if (datos.success && datos.count > 0){
        // Cabeceras de la tabla generadas dinámicamente según los campos del primer libro
        document.getElementById('tablaLibros').innerHTML =
            `<tr class="encabezado">` +
            Object.keys(libros[0]).map(clave =>
                `<td>${clave.toUpperCase()}</td>
                ${clave == 'resumen' ? '<td class="centrado" colspan="2">Acciones</td>' : ''}`
            ).join('') +
            "</tr>";

        // Cuerpo de la tabla: muestra cada libro con sus campos y botones de acción
        document.getElementById('tablaLibros').innerHTML +=
            libros.map(libro => `
            <tr>
                <td>${libro.id}</td>
                <td>${libro.titulo}</td>
                <td>${libro.autor}</td>
                <td>${libro.genero}</td>
                <td>${libro.fecha_publicacion}</td>
                <td>${(libro.imagen && libro.imagen.trim() !== '') ? `<img src="../img/img_peques/${libro.imagen}?${new Date().getTime()}" alt="${libro.titulo}" />` : 'Sin imagen'}</td>
                <td class="centrado">${(libro.disponible == 1) ? "Sí" : "No"}</td>
                <td class="centrado">${(libro.favorito == 1) ? "Sí" : "No"}</td>
                <td>${(libro.resumen !== null && libro.resumen.length > 0) ? libro.resumen.substring(0, 100) + "..." : ''}</td>
                <td><button onclick="editarLibro(${libro.id})">Editar</button></td>
                <td><button onclick="eliminarLibro(${libro.id}, '${libro.titulo}')" class="btn-delete">Eliminar</button></td>
            </tr>
        `).join('');
    } else if (datos.count == 0){
        document.getElementById('tablaLibros').innerHTML = "<p>No hay libros</p>";
    }
}

// Función para eliminar un libro usando su ID
function eliminarLibro(id, titulo) {
    const confirma = confirm(`¿Seguro que quieres eliminar el libro: ${titulo}?`);

    if (!confirma) return;

    // Petición DELETE a la API
    //el usuario ha confirmado que quiere eliminar el libro
    fetch(`${url}/${id}`, {
        method: 'DELETE'
    })
        .then(response => response.json())
        .then(data => libroEliminado(data))
        .catch(error => console.error('Error:', error));
}

// Función que se ejecuta tras eliminar un libro
function libroEliminado(data) {
    if (data.success) {
        // Recarga los libros después de eliminar
        fetch(url)
            .then(response => response.json())
            .then(data => mostrarLibros(data))
            .catch(error => console.error('Error:', error));
    } else {
        // Mensaje divertido si ocurre un error al eliminar
        alert("Hubo un problema al eliminar el libro, y más si es Harry Potter, eso no se elimina cariño");
    }
}

// Función aún sin implementar para editar libros
function editarLibro(id){
    //ir al inicio de la pagina
    window.scrollTo({top: 0, behavior: 'smooth'})

    //buscamos el libro que queremos modificar
    const libro = librosData.find(lib => lib.id == id)

    if(libro){
        //activar el modo edicion
        modoEdicion = true
        libroEditandoId = id

        //rellenamos el formulario con el libro que queremos editar
        rellenarFormularioEdicion(libro)

        //mostramos formulario en modo edicion
        mostrarFormularioEdicion()
    }else{
        alert ("Error: No se encontraron los datos del libro")
    }
}

function rellenarFormularioEdicion(libro){
    document.getElementById('titulo').value = libro.titulo || ''
    document.getElementById('autor').value = libro.autor || ''
    document.getElementById('genero').value = libro.genero || ''
    document.getElementById('fecha_publicacion').value = libro.fecha_publicacion || ''
    document.getElementById('disponible').checked = libro.disponible == 1
    document.getElementById('favorito').checked = libro.favorito == 1
    document.getElementById('resumen').value = libro.resumen || ''

    //limpiar el campo de la imagen
    document.getElementById('imagen'),value = ''

    //mostrar la imagen actual si existe
    mostrarImagenActual(libro.imagen, libro.titulo)
}

function mostrarFormularioEdicion(){
    //Mostrar formulario
    document.querySelector('form').style.display = 'grid'

    //cambiar los textos para el modo edicion
    document.querySelector('form h2').textContent = "Editar libro"
    document.getElementById('btnGuardar').textContent = "Actualizar libro"
    document.getElementById("crear").textContent = "Ocultar formulario"
}

function mostrarImagenActual(imagen, titulo){
    //eliminar imagen previa si exite
    const imagenPrevia = document.getElementById('imagen-actual')
    if(imagenPrevia){
        imagenPrevia.remove()
    }

    //comprobamos que el libro tiene imagen
    if(imagen && imagen.trim() !== ''){
        //crear un elemento para mostrar la imagen actual
        const divImagen = document.createElement('div')
        divImagen.id = 'imagen-actual'
        divImagen.innerHTML = `
        <p><strong>Imagen actual</strong></p>
        <img class="imagenEditar" src="../img/img_peques/${imagen}?${new Date().getTime()}" alt="${titulo}"/>
        <p>Selecciona una nueva imagen para reemplazarla</p>
        `

        //mostrar el divImagen despues del input de imagen
        const inputImagen = document.getElementById('imagen')
        inputImagen.before(divImagen)
    }
}

// Función para enviar los datos del formulario para crear un nuevo libro
function enviarDatosNuevoLibro(e) {
    e.preventDefault(); // Previene el envío normal del formulario

    const mensajesError = document.querySelectorAll('.error');

    // Obtiene los valores del formulario
    const titulo = document.getElementById('titulo').value.trim();
    const autor = document.getElementById('autor').value.trim();
    const genero = document.getElementById('genero').value.trim();
    const fecha_publicacion = parseInt(document.getElementById('fecha_publicacion').value);
    const imagen = document.getElementById('imagen').files[0];
    const disponible = document.getElementById('disponible').checked;
    const favorito = document.getElementById('favorito').checked;
    const resumen = document.getElementById('resumen').value.trim();

    //Limpiar mensajes de error previos
    mensajesError.forEach(elemento => elemento.textContent = '')    


    let errores = false;

    // Validaciones
    if (!titulo){
        document.getElementById('error-titulo').textContent = "El título es obligatorio";
        errores = true;
    }

    if (!autor){
        document.getElementById('error-autor').textContent = "El autor es obligatorio";
        errores = true;
    }

    const anioActual = new Date().getFullYear();
    if (isNaN(fecha_publicacion) || fecha_publicacion < 1000 || fecha_publicacion > anioActual + 1) {
        document.getElementById('error-publicacion').textContent = "La fecha de publicación debe ser un año válido (4 dígitos)";
        errores = true;
    }

    if (resumen.length > 1000){
        document.getElementById('error-resumen').textContent = "El resumen no puede superar los 1000 caracteres";
        errores = true;
    }

    if (imagen){
        const validacionImagen = validarImagen(imagen);
        if (!validacionImagen.esValido) {
            document.getElementById('error-imagen').textContent = validacionImagen.mensaje;
            errores = true;
        }
    }

    if (errores) return; // Detiene el envío si hay errores

    
    //Si estamos aquí los datos del formulario son válidos
    // const datos = {
    //     titulo: titulo,
    //     autor: autor,
    //     genero: genero,
    //     fecha_publicacion: fecha_publicacion,
    //     disponible: disponible,
    //     favorito: favorito,
    //     resumen: resumen
    // }

    // Objeto con los datos del formulario son validos
    const datos = {
        titulo,
        autor,
        genero,
        fecha_publicacion,
        disponible,
        favorito,
        resumen
    }

    // Utiliza FormData para enviar datos y archivo juntos
    const formData = new FormData();
    formData.append("datos", JSON.stringify(datos));
    
    if (imagen){
        formData.append("imagen", imagen);
    }


    const metodo = 'POST'; // Solo se usa POST para crear en este caso
    const urlPeticion = modoEdicion ? `${url}/${libroEditandoId}` : url
    const mensajeExito = modoEdicion ? "Libro actualizado con éxito" : "Libro guardado con éxito"

    //si estamos en modo edicion añadimos un parámetro _method
    if(modoEdicion){
        formData.append("_method", "PUT")
    }

    // Petición para guardar el nuevo libro
    fetch(urlPeticion, {
        method: metodo,
        body: formData //van los datos y el archivo
    })
        .then(response => {
        console.log(response)
        return response.json()
    })
        .then(data => {
            if (data.success){
                alert(mensajeExito);

                //limpio el formulario
                document.querySelector('form').reset();

                //oculto el formulario
                document.querySelector('form').style.display = "none";

                //cambio el texto del boton
                document.getElementById("crear").textContent = "Crear nuevo libro";

                resetearModoCreacion()

                //volvemos a pedir todos los libros para que se nos reflejen los cambios
                // Recargar los libros después de añadir uno nuevo
                cargarLibros()

            } else{
                alert("Upsi... algo ha salido mal...", data.error);
            }
        })
        
        .catch(error => {
            console.log("Error al enviar datos: ", error);
            const accion = modoEdicion ? "actualizar" : "guardar"
            alert(`Error al ${accion} el libro`)
        })
}

function cargarLibros(){
    fetch(url)
    .then(response => response.json())
    .then(data => mostrarLibros(data)) // Mostrar la lista actualizada
    .catch(error => console.error('Error: ', error));
}

// Función para validar la imagen seleccionada
function validarImagen(archivo) {
    console.log('Archivo tipo: ', archivo.type);
    console.log('Tamaño del archivo: ', archivo.size);

    if (!archivo){
        return {
            esValido: true,
            mensaje: ""
        }
    }

    // Tipos de imagen permitidos
    const tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!tiposPermitidos.includes(archivo.type)){
        return{
            esValido: true,
            mensaje: "Solo se permiten archivos de imagen (JPEG, JPG, PNG, GIF, WebP)"
        }
    }

    // Tamaño máximo permitido: 1MB
    const tamanioMaximo = 1024 * 1024;
    const tamanioMaximoMB = 1;
    if (archivo.size > tamanioMaximo){
        return{
            esValido: false,
            mensaje: `La imagen no puede superar los ${tamanioMaximoMB} MB. Tamaño actual: ${(archivo.size / (1024 * 1024)).toFixed(2)}MB.`
        }
    }

    // Tamaño mínimo: mayor a 1KB
    const tamanioMinimo = 1024;
    if (archivo.size < tamanioMinimo){
        return{
            esValido: false,
            mensaje: "El archivo de la imagen está vacío."
        }
    }

    // Si pasa todas las validaciones
    return{
        esValido: true,
        mensaje: ''
    }
}

function resetearModoCreacion(){
    modoEdicion = false
    libroEditandoId = null

    //restauramos los textos originales
    document.querySelector('form h2').textContent = "Nuevo libro"
    document.getElementById('btnGuardar').textContent = 'Guardar libro'

    //eliminar la imagen actual si exixte
    const imagenPrevia = document.getElementById('imagen-actual')
    if(imagenPrevia){
        imagenPrevia.remove()
    }

    document.querySelector('form').reset()
}