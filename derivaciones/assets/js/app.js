// Datos de ejemplo para médicos por sector
let medicosPorSector = {
    'Guardia': ['Dr. Pérez', 'Dra. Gómez'],
    'Internación': ['Dr. López', 'Dra. Martínez'],
    'Terapia Intensiva': ['Dr. Fernández', 'Dra. Torres']
};

// Cargar usuario logueado en campo administrativo
function cargarUsuarioAdministrativo() {
    const usuario = sessionStorage.getItem('usuarioLogueado') || '';
    document.getElementById('administrativo').value = usuario;
    // También actualizar el mensaje de bienvenida aquí
    const bienvenidaDiv = document.getElementById('bienvenida');
    if (bienvenidaDiv) {
        if (usuario) {
            bienvenidaDiv.textContent = `¡Bienvenido/a ${usuario}!`;
            bienvenidaDiv.style.display = 'block';
        } else {
            bienvenidaDiv.textContent = '';
            bienvenidaDiv.style.display = 'none';
        }
    }
}

// Actualizar médicos según sector seleccionado
function actualizarMedicos() {
    const sector = document.getElementById('sector').value;
    const medicoSelect = document.getElementById('medico');
    medicoSelect.innerHTML = '<option value="">Seleccione un médico</option>';
    if (medicosPorSector[sector]) {
        medicosPorSector[sector].forEach(medico => {
            const option = document.createElement('option');
            option.value = medico;
            option.textContent = medico;
            medicoSelect.appendChild(option);
        });
        // Agregar opción Otros
        const optionOtros = document.createElement('option');
        optionOtros.value = '__otros__';
        optionOtros.textContent = 'Otros';
        medicoSelect.appendChild(optionOtros);
    }
    document.getElementById('alta-medico').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', () => {
    cargarUsuarioAdministrativo();
    document.getElementById('sector').addEventListener('change', actualizarMedicos);
    document.getElementById('medico').addEventListener('change', function() {
        if (this.value === '__otros__') {
            document.getElementById('alta-medico').style.display = 'block';
        } else {
            document.getElementById('alta-medico').style.display = 'none';
        }
    });
    document.getElementById('guardarNuevoMedico').addEventListener('click', function() {
        const sector = document.getElementById('sector').value;
        const nuevoMedico = document.getElementById('nuevo-medico-nombre').value.trim();
        if (!nuevoMedico) {
            alert('Ingrese nombre y apellido del médico.');
            return;
        }
        if (!medicosPorSector[sector]) {
            medicosPorSector[sector] = [];
        }
        medicosPorSector[sector].push(nuevoMedico);
        actualizarMedicos();
        document.getElementById('medico').value = nuevoMedico;
        document.getElementById('alta-medico').style.display = 'none';
        document.getElementById('nuevo-medico-nombre').value = '';
        alert('Médico agregado correctamente.');
    });
    document.getElementById('cerrarSesion').addEventListener('click', async function() {
        // Limpiar sessionStorage
        sessionStorage.clear();
        // Llamar al backend para limpiar variables de sesión
        try {
            await fetch('/assets/control/ldap_login/metodo.php?metodo=cerrarSesion', { method: 'POST' });
        } catch (e) {}
        window.location.href = 'login.html';
    });
    document.getElementById('derivation-form').addEventListener('submit', function(e) {
        e.preventDefault();
        // Validación básica
        const nombre = document.getElementById('nombre').value.trim();
        const obraSocial = document.getElementById('obra-social').value;
        const diagnostico = document.getElementById('diagnostico').value.trim();
        const administrativo = document.getElementById('administrativo').value;
        const sector = document.getElementById('sector').value;
        const medico = document.getElementById('medico').value;
        const estado = document.getElementById('estado').value;
        if (!nombre || !obraSocial || !diagnostico || !administrativo || !sector || !medico || !estado) {
            alert('Por favor complete todos los campos obligatorios.');
            return;
        }
        // Guardar datos en localStorage
        const derivacion = {
            nombre,
            obraSocial,
            diagnostico,
            administrativo,
            sector,
            medico,
            estado,
            observaciones: document.getElementById('observaciones').value.trim()
        };
        let derivaciones = JSON.parse(localStorage.getItem('derivaciones') || '[]');
        derivaciones.push(derivacion);
        localStorage.setItem('derivaciones', JSON.stringify(derivaciones));
        alert('Derivación enviada correctamente.');
        this.reset();
        cargarUsuarioAdministrativo();
        actualizarMedicos();
    });
});