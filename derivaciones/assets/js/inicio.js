$(document).ready(function() {
	$('#loginForm').on('submit', async function(e) {
		e.preventDefault(); // Evitar envío por default del form

		let usuario = $('#username').val().trim();
		let contraseña = $('#password').val();

		if (!usuario || !contraseña) {
			alert('Por favor complete todos los campos.');
			return;
		}

		try {
			const response = await fetch(`assets/control/ldap_login/metodo.php?metodo=validacion&usuario=${encodeURIComponent(usuario)}`, {
				method: 'POST',
				headers: {
					'Accept': 'application/json',
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({ contraseña })
			});

			const data = await response.json();
			// Tomar el usuario de la respuesta y guardarlo en sessionStorage
			// Usar las claves con acento si existen
			if (data.variables && (data.variables.validacion || data.variables["validación"]) && data.variables.user) {
				sessionStorage.setItem('usuarioLogueado', data.variables.user);
				window.location.href = '../../home.html';
			} else {
				// Si no viene el usuario, limpiar el valor
				sessionStorage.removeItem('usuarioLogueado');
				alert('Usuario o contraseña incorrectos o sin permisos.');
			}

		} catch (error) {
			console.error('Error de conexión:', error);
			alert('Error de conexión con el servidor.');
		}
	});
});
