//comprobar que las contraseñas coinciden
document.getElementById('formRestablecer').addEventListener('submit', (e) => {
    const nueva = document.getElementById('nuevaPassword').value;
    const confirmar = document.getElementById('confirmarPassword').value;
    const mensaje = document.getElementById('mensaje_cliente');

    if(nueva !== confirmar){
        e.preventDefault();
        mensaje.textContent = "Las contraseñas no coinciden";
        mensaje.style.display = 'block';
    }
})