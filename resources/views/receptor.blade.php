<!DOCTYPE html> 
<html lang="es"> 
<head> 
  <meta charset="UTF-8"> 
  <title>Receptor</title> 
  @vite(['resources/css/app.css','resources/js/app.js']) 
  <style> 
    body { font-family: system-ui, sans-serif; padding: 24px; } 
    .wrap { max-width: 760px; margin: 0 auto; } 
    #target-div { padding: 20px; border: 1px dashed #bbb; border-radius: 12px; } 
  </style> 
</head> 
<body> 
<div class="wrap"> 
  <h1>Receptor</h1> 
  <div id="target-div">Esperando mensaje...</div> 
</div> 
 
<script>
document.addEventListener('DOMContentLoaded', function () {
    const targetDiv = document.getElementById('target-div');

    if (!window.Echo) {
        console.error('ERROR: Laravel Echo no está inicializado');
        targetDiv.innerText = 'Error: Laravel Echo no está disponible';
        return;
    }

    console.log('Laravel Echo inicializado correctamente');

    const pusher = window.Echo.connector.pusher;

    pusher.connection.bind('connected', () => {
        console.log('WebSocket conectado a Laravel Reverb');
    });

    pusher.connection.bind('error', (error) => {
        console.error('Error de conexión WebSocket:', error);
    });

    window.Echo.channel('style-channel')
        .listen('.RemoteStyleUpdate', (e) => {
            console.log('Evento recibido:', e);

            targetDiv.innerText = e.newText ?? '';
            targetDiv.style.cssText = e.newStyle ?? '';
        });
});
</script>
</body> 
</html>