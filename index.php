
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
        // Asumimos que este JSON viene de PHP
        const errorData = <?php echo json_encode($erroresDeBaseDeDatos); ?>;

        $(document).ready(function() {
            let processedContent = '';
            let changes = [];

            // Función para leer el archivo PSR
            function readPSRFile(file) {
                return new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onload = (e) => resolve(e.target.result);
                    reader.onerror = (e) => reject(e);
                    reader.readAsText(file);
                });
            }

            // Función para procesar el contenido
            function processContent(psrContent) {
                const errorMap = new Map(errorData.map(error => [error.code, error]));
                const xerroxRegex = /XERROX\(([^,]+),\s*'([^']+)'\)/g;
                changes = [];

                const lines = psrContent.split('\n');
                const processedLines = lines.map((line, index) => {
                    if (!line.includes('XERROX')) return line;

                    const processedLine = line.replace(xerroxRegex, (match, code, articleDesc) => {
                        const trimmedCode = code.trim();
                        const errorInfo = errorMap.get(trimmedCode);

                        if (errorInfo) {
                            const newLine = `XERROX(${trimmedCode}, '${articleDesc} - ${errorInfo.description}')`;
                            changes.push({
                                lineNumber: index + 1,
                                original: line,
                                processed: newLine
                            });
                            return newLine;
                        }
                        return match;
                    });

                    return processedLine;
                });

                return processedLines.join('\n');
            }

            // Evento de procesamiento
            $('#processBtn').click(async function() {
                try {
                    const psrFile = $('#psrFile')[0].files[0];
                    if (!psrFile) {
                        alert('Por favor, selecciona un archivo PSR');
                        return;
                    }

                    const psrContent = await readPSRFile(psrFile);
                    processedContent = processContent(psrContent);

                    // Mostrar resumen
                    $('#processingSummary').html(`
                        <div class="p-4 bg-green-100 dark:bg-green-900 rounded-lg">
                            <p class="text-green-800 dark:text-green-200">
                                Archivo procesado exitosamente<br>
                                Total de cambios: ${changes.length}<br>
                                Nombre del archivo: ${psrFile.name}
                            </p>
                        </div>
                    `);

                    $('#resultsSection').removeClass('hidden');
                } catch (error) {
                    alert('Error al procesar el archivo: ' + error.message);
                }
            });

            // Descargar archivo procesado
            $('#downloadBtn').click(function() {
                const blob = new Blob([processedContent], { type: 'text/plain' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'processed_' + $('#psrFile')[0].files[0].name;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            });

            // Mostrar modal de cambios
            $('#showChangesBtn').click(function() {
                const $changesList = $('#changesList').empty();
                
                changes.forEach(change => {
                    $changesList.append(`
                        <div class="p-4 bg-gray-100 dark:bg-slate-700 rounded-lg">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Línea ${change.lineNumber}:</p>
                            <p class="text-red-600 dark:text-red-400 mb-2">Original:<br>${change.original}</p>
                            <p class="text-green-600 dark:text-green-400">Procesado:<br>${change.processed}</p>
                        </div>
                    `);
                });

                $('#changesModal').removeClass('hidden');
            });

            // Cerrar modal
            $('#closeModal').click(function() {
                $('#changesModal').addClass('hidden');
            });
        });
    </script>
</body>
</html>
