document.addEventListener('DOMContentLoaded', () => {
  const output = document.getElementById('chat-output');
  if (!output) return;

  const eventSource = new EventSource('/wp-admin/admin-ajax.php?action=stream_chat');

  eventSource.onmessage = function (event) {
    if (event.data === '[DONE]') {
      eventSource.close();
      return;
    }
    try {
      const data = JSON.parse(event.data);
      if (data.choices) {
        output.innerHTML += (data.choices[0].delta && data.choices[0].delta.content) || '';
      }
    } catch (e) {
      console.error('Error al parsear JSON del stream:', e, event.data);
    }
  };

  eventSource.onerror = function (error) {
    console.error('Error en el stream:', error);
    eventSource.close();
  };
});
