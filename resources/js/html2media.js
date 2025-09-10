document.addEventListener('DOMContentLoaded', () => {
    console.log('html2media.js loaded');

    // Check for required libraries
    if (!window.html2pdf) {
        console.error('html2pdf library not found!');
        return;
    }
    console.log('html2pdf library loaded:', !!window.html2pdf);
    console.log('jspdf available:', !!window.jsPDF);
    console.log('html2canvas available:', !!window.html2canvas);

    // Wait for Livewire to initialize
    document.addEventListener('livewire:init', () => {
        console.log('Livewire initialized for html2media.js');

        Livewire.on('triggerPrint', (options = []) => {
            console.log('triggerPrint event received:', JSON.stringify(options, null, 2));
            if (options.length > 0) {
                performAction(options[0]);
            } else {
                console.error('No options provided for triggerPrint');
            }
        });

        // Debug: Log Livewire availability
        console.log('Livewire available:', !!window.Livewire);
    });
});

function performAction({ action = 'print', element, ...customOptions } = {}) {
    console.log('performAction called with:', { action, element, customOptions });
    
    const tryFindElement = (retries = 5, delay = 500) => {
        const printElement = document.getElementById(element);
        console.log('Looking for element with ID:', element, 'Attempt:', 6 - retries);
        if (printElement) {
            processElement(printElement, action, customOptions);
        } else if (retries > 0) {
            console.warn(`Element "${element}" not found, retrying...`);
            setTimeout(() => tryFindElement(retries - 1, delay), delay);
        } else {
            console.error(`Element "${element}" not found after retries.`);
            console.log('Available elements:', Array.from(document.querySelectorAll('[id]')).map(el => el.id));
        }
    };

    tryFindElement();
}

function processElement(printElement, action, customOptions) {
    const defaultOptions = {
        filename: 'document.pdf',
        pagebreak: { mode: ['css', 'legacy'], after: 'section' },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
        html2canvas: { scale: 2, useCORS: true, allowTaint: true, logging: true },
        margin: 0
    };

    const options = {
        ...defaultOptions,
        ...customOptions,
        pagebreak: { ...defaultOptions.pagebreak, ...(customOptions.pagebreak || {}) },
        jsPDF: { ...defaultOptions.jsPDF, ...(customOptions.jsPDF || {}) },
        html2canvas: { ...defaultOptions.html2canvas, ...(customOptions.html2canvas || {}) }
    };

    console.log('Final options:', options);

    try {
        switch (action) {
            case 'savePdf':
                console.log('Starting PDF save...');
                html2pdf()
                    .from(printElement)
                    .set(options)
                    .save()
                    .then(() => console.log('PDF saved successfully'))
                    .catch(error => console.error('PDF save failed:', error));
                break;
            case 'print':
                console.log('Starting print...');
                html2pdf()
                    .from(printElement)
                    .set(options)
                    .toPdf()
                    .get('pdf')
                    .then(function (pdf) {
                        console.log('PDF generated for printing');
                        const blob = pdf.output('blob');
                        const url = URL.createObjectURL(blob);
                        const iframe = document.createElement('iframe');
                        iframe.id = `print-smart-iframe-${element.replace(/[^a-zA-Z0-9]/g, '')}`;
                        iframe.style.display = 'none';
                        document.body.appendChild(iframe);
                        iframe.src = url;
                        
                        iframe.onload = function () {
                            console.log('PDF loaded in iframe, triggering print');
                            iframe.contentWindow.focus();
                            iframe.contentWindow.print();
                            
                            iframe.contentWindow.onafterprint = function () {
                                console.log('Print dialog closed, cleaning up');
                                URL.revokeObjectURL(url);
                                iframe.remove();
                            };
                        };
                    })
                    .catch(error => console.error('Print failed:', error));
                break;
            case 'preview':
                console.log('Starting preview...');
                html2pdf()
                    .from(printElement)
                    .set(options)
                    .toPdf()
                    .get('pdf')
                    .then(function (pdf) {
                        console.log('PDF generated for preview');
                        const blob = pdf.output('blob');
                        const url = URL.createObjectURL(blob);
                        window.open(url, '_blank');
                        setTimeout(() => URL.revokeObjectURL(url), 5000);
                    })
                    .catch(error => console.error('Preview failed:', error));
                break;
            default:
                console.error('Unsupported action:', action);
        }
    } catch (error) {
        console.error('Error performing action:', error);
    }
}