async function subirImagenAFlask(file, subfolder) {
  const formData = new FormData();
  formData.append('file', file);
  if (subfolder) {
    formData.append('subfolder', subfolder);
  }

  const res = await fetch(url_flask + '/tk_guardar_img', {
    method: 'POST',
    body: formData
  });
  const data = await res.json();
  
  if (!data.job_id) {
    throw new Error('No se pudo encolar la imagen en el servidor Flask');
  }

  const jobId = data.job_id;

  while (true) {
    await new Promise(resolve => setTimeout(resolve, 1000));
    const statusRes = await fetch(`${url_flask}/jobs/result?job_id=${jobId}`);
    const statusData = await statusRes.json();

    if (statusData.status === 'finished') {
      if (statusData.result && statusData.result.ok) {
        return statusData.result.url;
      } else {
        throw new Error(statusData.result?.error || 'Error en el worker al procesar la imagen');
      }
    } else if (statusData.status === 'failed') {
      throw new Error('El trabajo de procesamiento de imagen falló en el servidor');
    }
  }
}