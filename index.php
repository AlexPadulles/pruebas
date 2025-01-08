
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesador de Archivos PSR</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 dark:bg-slate-900 min-h-screen p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-8 text-gray-800 dark:text-white">Procesador de Archivos PSR</h1>
        
        <div class="bg-white dark:bg-slate-800 p-6 rounded-lg shadow mb-6">
            <div class="mb-6">
                <label class="block text-gray-700 dark:text-gray-200 text-sm font-bold mb-2">
                    Archivo PSR
                </label>
                <input 
                    type="file" 
                    id="psrFile" 
                    accept=".psr,.txt"
                    class="block w-full text-sm text-gray-500 dark:text-gray-300
                           file:mr-4 file:py-2 file:px-4
                           file:rounded-md file:border-0
                           file:text-sm file:font-semibold
                           file:bg-blue-600 file:text-white
                           hover:file:bg-blue-700"
                >
            </div>

            <button 
                id="processBtn"
                class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors w-full">
                Procesar Archivo
            </button>
        </div>

        <!-- Results Section -->
        <div class="bg-white dark:bg-slate-800 p-6 rounded-lg shadow mb-6 hidden" id="resultsSection">
            <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-white">Resultados del Procesamiento</h2>
            <div id="processingSummary" class="mb-4"></div>
            <div class="flex justify-between">
                <button 
                    id="downloadBtn"
                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Descargar Archivo Procesado
                </button>
                <button 
                    id="showChangesBtn"
                    class="px-6 py-3 bg-violet-600 text-white rounded-lg hover:bg-violet-700 transition-colors">
                    Ver Cambios
                </button>
            </div>
        </div>

        <!-- Changes Modal -->
        <div id="changesModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center">
            <div class="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-lg w-3/4 max-h-[80vh] overflow-y-auto">
                <h3 class="text-xl font-semibold mb-4 text-gray-800 dark:text-white">Cambios Realizados</h3>
                <div id="changesList" class="space-y-4"></div>
                <button 
                    id="closeModal"
                    class="mt-6 px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

     <script>
                    // Obtenemos el JSON de errores que viene desde PHP y lo convertimos en un objeto JavaScript
            const errorData = <?php echo json_encode($erroresDeBaseDeDatos); ?>;

            // Cuando el documento esté listo, iniciamos nuestra funcionalidad
            $(document).ready(function() {
                // Variables para almacenar el contenido procesado y los cambios realizados
                let processedContent = ''; // Almacenará el archivo final procesado
                let changes = [];         // Almacenará todos los cambios realizados para mostrarlos después

                // Función que lee el archivo PSR que sube el usuario
                // Devuelve una Promise que resuelve con el contenido del archivo
                function readPSRFile(file) {
                    return new Promise((resolve, reject) => {
                        const reader = new FileReader();
                        // Cuando termine de leer, devuelve el contenido
                        reader.onload = (e) => resolve(e.target.result);
                        // Si hay error, lo rechaza
                        reader.onerror = (e) => reject(e);
                        // Comienza a leer el archivo como texto
                        reader.readAsText(file);
                    });
                }

                // Función principal que procesa el contenido del archivo PSR
                function processContent(psrContent) {
                    // Crear un Map para búsqueda rápida de errores por código
                    // Map es más eficiente que buscar en array para muchas búsquedas
                    const errorMap = new Map(errorData.map(error => [error.code, error]));

                    var errorMaps = new Map(errorData.map( function(error){
                        return [error.code, error];
                    }));
                    
                    // Expresión regular para encontrar patrones XERROX en el texto
                    // Busca: XERROX(código, 'descripción')
                    const xerroxRegex = /XERROX\(([^,]+),\s*'([^']+)'\)/g;

                    // Reiniciar el array de cambios
                    changes = [];

                    // Dividir el contenido en líneas y procesar cada una
                    const lines = psrContent.split('\n');
                    const processedLines = lines.map((line, index) => {
                        // Si la línea no contiene XERROX, la dejamos igual
                        if (!line.includes('XERROX')) return line;

                        // Reemplazar cada ocurrencia de XERROX en la línea
                        const processedLine = line.replace(xerroxRegex, (match, code, articleDesc) => {
                            const trimmedCode = code.trim();
                            // Buscar si existe un error con ese código
                            const errorInfo = errorMap.get(trimmedCode);

                            if (errorInfo) {
                                // Si encontramos el error, creamos la nueva línea concatenando las descripciones
                                const newLine = `XERROX(${trimmedCode}, '${articleDesc} - ${errorInfo.description}')`;
                                // Guardar el cambio para mostrarlo después
                                changes.push({
                                    lineNumber: index + 1,
                                    original: line,
                                    processed: newLine
                                });
                                return newLine;
                            }
                            // Si no encontramos el error, dejamos la línea original
                            return match;
                        });

                        return processedLine;
                    });

                    // Unir todas las líneas procesadas de nuevo en un solo texto
                    return processedLines.join('\n');
                }

                // Manejador del botón de procesar
                $('#processBtn').click(async function() {
                    try {
                        // Obtener el archivo seleccionado
                        const psrFile = $('#psrFile')[0].files[0];
                        if (!psrFile) {
                            alert('Por favor, selecciona un archivo PSR');
                            return;
                        }

                        // Leer y procesar el archivo
                        const psrContent = await readPSRFile(psrFile);
                        processedContent = processContent(psrContent);

                        // Mostrar resumen del procesamiento
                        $('#processingSummary').html(`
                            <div class="p-4 bg-green-100 dark:bg-green-900 rounded-lg">
                                <p class="text-green-800 dark:text-green-200">
                                    Archivo procesado exitosamente<br>
                                    Total de cambios: ${changes.length}<br>
                                    Nombre del archivo: ${psrFile.name}
                                </p>
                            </div>
                        `);

                        // Mostrar la sección de resultados
                        $('#resultsSection').removeClass('hidden');
                    } catch (error) {
                        alert('Error al procesar el archivo: ' + error.message);
                    }
                });

                // Manejador del botón de descarga
                // Crea un archivo temporal y lo descarga automáticamente
                $('#downloadBtn').click(function() {
                    // Crear un blob con el contenido procesado
                    const blob = new Blob([processedContent], { type: 'text/plain' });
                    // Crear URL temporal para el blob
                    const url = window.URL.createObjectURL(blob);
                    // Crear elemento <a> temporal
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'processed_' + $('#psrFile')[0].files[0].name;
                    // Agregar al documento, hacer clic y limpiar
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                });

                // Manejador para mostrar los cambios en un modal
                $('#showChangesBtn').click(function() {
                    const $changesList = $('#changesList').empty();

                    // Por cada cambio, crear un elemento visual que muestre el antes y después
                    changes.forEach(change => {
                        $changesList.append(`
                            <div class="p-4 bg-gray-100 dark:bg-slate-700 rounded-lg">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Línea ${change.lineNumber}:</p>
                                <p class="text-red-600 dark:text-red-400 mb-2">Original:<br>${change.original}</p>
                                <p class="text-green-600 dark:text-green-400">Procesado:<br>${change.processed}</p>
                            </div>
                        `);
                    });

                    // Mostrar el modal
                    $('#changesModal').removeClass('hidden');
                });

                // Manejador para cerrar el modal de cambios
                $('#closeModal').click(function() {
                    $('#changesModal').addClass('hidden');
                });
            });
        </script>
</body>
</html>
