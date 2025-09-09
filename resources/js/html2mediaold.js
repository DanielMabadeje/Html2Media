document.addEventListener('DOMContentLoaded', function () {
    Livewire.on('triggerPrint', function (options = {}) {
        console.log('triggerPrint', options);

        performAction(options);
    });
});

// function performAction({ action = 'print', element, ...customOptions } = {}) {
//     const printElement = document.getElementById(`print-smart-content-${element}`);

//     // Default options for html2pdf
//     const defaultOptions = {
//         filename: 'document.pdf',
//         pagebreak: {
//             mode: ['css', 'legacy'],
//             after: 'section'
//         },
//         jsPDF: {
//             unit: 'mm',
//             format: 'a4',
//             orientation: 'portrait'
//         },
//         html2canvas: {
//             scale: 2,
//             useCORS: true,
//             logging: true
//         },
//         margin: 0
//     };

//     // Merge custom options with defaults
//     const options = {
//         ...defaultOptions,
//         ...customOptions,
//         pagebreak: {
//             ...defaultOptions.pagebreak,
//             ...(customOptions.pagebreak || {})
//         },
//         jsPDF: {
//             ...defaultOptions.jsPDF,
//             ...(customOptions.jsPDF || {})
//         },
//         html2canvas: {
//             ...defaultOptions.html2canvas,
//             ...(customOptions.html2canvas || {})
//         }
//     };

//     if (printElement) {
//         switch (action) {
//             case 'savePdf':
//                 // Save as PDF
//                 html2pdf()
//                     .from(printElement)
//                     .set(options)
//                     .save();
//                 break;
//             case 'print':
//                 // Print action
//                 html2pdf()
//                     .from(printElement)
//                     .set(options)
//                     .toPdf()
//                     .get('pdf')
//                     .then(function (pdf) {
//                         const blob = pdf.output('blob');
//                         const url = URL.createObjectURL(blob);
//                         const iframe = document.getElementById(`print-smart-iframe-${element}`);
//                         iframe.src = url;

//                         iframe.onload = function () {
//                             iframe.contentWindow.focus();
//                             iframe.contentWindow.print();
//                             iframe.contentWindow.onafterprint = function () {
//                                 URL.revokeObjectURL(url);
//                             };
//                         };
//                     });
//                 break;
//             default:
//                 console.error('Unsupported action:', action);
//         }
//     } else {
//         console.error(`Element with ID "print-smart-content-${element}" not found.`);
//     }
// }
function performAction({ action = 'print', element, ...customOptions } = {}) {
    const action = type;
    const printElement = document.getElementById(`print-smart-content-${element}`);
    if (!printElement) {
        console.error(`Element with ID "print-smart-content-${element}" not found.`);
        return;
    }

    // Create iframe dynamically if it doesn't exist
    let iframe = document.getElementById(`print-smart-iframe-${element}`);
    if (!iframe) {
        iframe = document.createElement('iframe');
        iframe.id = `print-smart-iframe-${element}`;
        iframe.style.display = 'none';
        document.body.appendChild(iframe);
    }

    const defaultOptions = {
        filename: 'document.pdf',
        pagebreak: {
            mode: ['css', 'legacy'],
            after: 'section'
        },
        jsPDF: {
            unit: 'mm',
            format: 'a4',
            orientation: 'portrait'
        },
        html2canvas: {
            scale: 2,
            useCORS: true,
            logging: true
        },
        margin: 0
    };

    const options = {
        ...defaultOptions,
        ...customOptions,
        pagebreak: { ...defaultOptions.pagebreak, ...(customOptions.pagebreak || {}) },
        jsPDF: { ...defaultOptions.jsPDF, ...(customOptions.jsPDF || {}) },
        html2canvas: { ...defaultOptions.html2canvas, ...(customOptions.html2canvas || {}) }
    };

    switch (action) {
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
                        iframe.remove(); // Clean up iframe
                    };
                };
            }).catch(function (error) {
                console.error('Print error:', error);
            });
            break;
        default:
            console.error('Unsupported action:', action);
    }
}

function replaceSpacesInTextNodes(element) {
    element.childNodes.forEach(node => {
        if (node.nodeType === Node.TEXT_NODE && node.textContent.trim() !== '') {
            node.textContent = node.textContent.replace(/\s/g, "\u00a0");
        } else if (node.nodeType === Node.ELEMENT_NODE) {
            replaceSpacesInTextNodes(node);
        }
    });
}
