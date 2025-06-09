// Crear evento para que se ejecute el código cuando haya terminado de cargarse el DOM
document.addEventListener('DOMContentLoaded', () => {
    const url = 'http://localhost/ApiBiblioteca/api/libros';

    // Realizo la llamada a la API para conseguir los datos
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(data => {
            console.log(data);
            mostrarLibros(data);
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('divLibros').innerHTML = 
                '<div class="error">Error al cargar los libros. Por favor, inténtalo de nuevo.</div>';
        });
});

function mostrarLibros(datos) {
    const divLibros = document.getElementById('divLibros');
    
    if (datos.success && datos.count > 0) {
        const libros = datos.data;
        console.log(libros);

        // Muestro los libros de la lista
        divLibros.innerHTML = libros.map(libro => `
            <div class="libroCard">
                <div class="libro-imagen-container">
                    <img src="img/img_peques/${libro.imagen}" 
                         alt="${libro.titulo}"
                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDIwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iMjAwIiBmaWxsPSIjZjNlOGZmIi8+CjxwYXRoIGQ9Ik04MCA2MEgxMjBWMTQwSDgwVjYwWiIgZmlsbD0iIzhiNWNmNiIvPgo8cGF0aCBkPSJNOTAgOTBIMTEwVjEyMEg5MFY5MFoiIGZpbGw9IiNmM2U4ZmYiLz4KPC9zdmc+Cg=='"
                         loading="lazy" />
                </div>
                <div class="libro-info">
                    <h3 class="libro-titulo">${libro.titulo}</h3>
                    <p class="libro-resumen">${libro.resumen || 'Sin resumen disponible'}</p>
                </div>
            </div>
        `).join('');

    } else if (datos.count === 0) {
        divLibros.innerHTML = '<div class="no-libros">No hay libros disponibles en la biblioteca</div>';
    } else {
        divLibros.innerHTML = '<div class="error">Error al cargar los datos de los libros</div>';
    }
}