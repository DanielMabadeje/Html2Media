// Fixed html2media.js
document.addEventListener('DOMContentLoaded', function () {
    console.log('html2media.js loaded');
    
    // Check for required libraries
    if (!window.html2pdf) {
        console.error('html2pdf library not found!');
        return;
    }
    
    Livewire.on('triggerPrint', function (options = []) {
        console.log('triggerPrint received:', JSON.stringify(options, null, 2));
        if (options.length > 0) {
            performAction(options[0]);
        } else {
            console.error('No options provided for triggerPrint');
        }
    });
});

function performAction({ action = 'print', element, ...customOptions } = {}) {
    console.log('performAction called with:', { action, element, customOptions });
    
    // FIXED: Use the element ID directly, don't construct it
    const printElement = document.getElementById(element);
    
    console.log('Looking for element with ID:', element);
    console.log('Element found:', printElement);

    if (!printElement) {
        console.error(`Element with ID "${element}" not found.`);
        
        // Debug: List all available elements
        const allElements = document.querySelectorAll('[id]');
        console.log('Available elements:', Array.from(allElements).map(el => el.id));
        
        return;
    }

    // Create or reuse iframe for printing
    let iframe = document.getElementById(`print-smart-iframe-${element.replace(/[^a-zA-Z0-9]/g, '')}`);
    if (!iframe) {
        iframe = document.createElement('iframe');
        iframe.id = `print-smart-iframe-${element.replace(/[^a-zA-Z0-9]/g, '')}`;
        iframe.style.display = 'none';
        document.body.appendChild(iframe);
    }

    const defaultOptions = {
        filename: 'document.pdf',
        pagebreak: { mode: ['css', 'legacy'], after: 'section' },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
        html2canvas: { scale: 2, useCORS: true, logging: true },
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
                    .then(() => {
                        console.log('PDF saved successfully');
                    })
                    .catch(error => {
                        console.error('PDF save failed:', error);
                    });
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
                    .catch(error => {
                        console.error('Print failed:', error);
                    });
                break;
                
            case 'preview':
                console.log('Starting preview...');
                html2pdf()
                    .from(printElement)
                    .set(options)
                    .toPdf()
                    .get('pdf')
                    .then(function (pdf) {
                        const blob = pdf.output('blob');
                        const url = URL.createObjectURL(blob);
                        window.open(url, '_blank');
                        
                        // Clean up after a delay
                        setTimeout(() => URL.revokeObjectURL(url), 5000);
                    })
                    .catch(error => {
                        console.error('Preview failed:', error);
                    });
                break;
                
            default:
                console.error('Unsupported action:', action);
        }
    } catch (error) {
        console.error('Error performing action:', error);
    }
}