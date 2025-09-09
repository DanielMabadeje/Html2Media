document.addEventListener('DOMContentLoaded', function () {
    console.log('html2media.js loaded');
    Livewire.on('triggerPrint', function (options = []) {
        console.log('triggerPrint received:', JSON.stringify(options, null, 2));
        if (options.length > 0) {
            performAction(options[0]);
        } else {
            console.error('No options provided for triggerPrint');
        }
    });
});

function performAction({ type = 'print', element, ...customOptions } = {}) {
    const printElement = document.getElementById(`print-smart-content-${element}`);
    if (!printElement) {
        console.error(`Element with ID "print-smart-content-${element}" not found.`);
        return;
    }

    let iframe = document.getElementById(`print-smart-iframe-${element}`);
    if (!iframe) {
        iframe = document.createElement('iframe');
        iframe.id = `print-smart-iframe-${element}`;
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

    try {
        switch (type) {
            case 'savePdf':
                html2pdf().from(printElement).set(options).save();
                break;
            case 'print':
                html2pdf().from(printElement).set(options).toPdf().get('pdf').then(function (pdf) {
                    const blob = pdf.output('blob');
                    const url = URL.createObjectURL(blob);
                    iframe.src = url;
                    iframe.onload = function () {
                        iframe.contentWindow.focus();
                        iframe.contentWindow.print();
                        iframe.contentWindow.onafterprint = function () {
                            URL.revokeObjectURL(url);
                            iframe.remove();
                        };
                    };
                });
                break;
            default:
                console.error('Unsupported action:', type);
        }
    } catch (error) {
        console.error('Error performing action:', error);
    }
}