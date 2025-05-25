jQuery(document).ready(function($) {
    // Handle CSV file upload
    $('#bulk_removal_csv_file').change(function() {
        $('#bulk_removal_csv_form').submit();
    });

    // Initialize variables
    let progressBar = $('#progress-bar');
    let progressContainer = $('#progress-container');
    let deletedProductsCount = $('#deleted_products_cnt');
    let totalProducts = $('#total_products');
    let successMessage = $('#success-message');
    let productDeletedResult = $('#product-deleted-result');
    let currentPercentage = 0;

    // Cookie management functions
    function setCookie(name, value, days) {
        let expires = "";
        if (days) {
            let date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    }

    function getCookie(name) {
        let nameEQ = name + "=";
        let ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }

    function eraseCookie(name) {
        document.cookie = name + '=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
    }

    // Progress bar update function
    function updateProgress(initialValue, endValue) {
        currentPercentage = initialValue;
        let interval = setInterval(function() {
            if (currentPercentage <= endValue) {
                progressBar.css('width', currentPercentage + '%');
                currentPercentage++;
            } else {
                clearInterval(interval);
            }
        }, 50);
    }

    // Collect product IDs
    let productIDs = [];
    $('.remove_product_ids li').each(function() {
        productIDs.push($(this).attr('id'));
    });
    totalProducts.text(productIDs.length);

    // Process products in batches
    let batchSize = 5;
    let batches = [];
    for (let i = 0; i < productIDs.length; i += batchSize) {
        batches.push(productIDs.slice(i, i + batchSize));
    }

    function processBatches(index) {
        if (index < batches.length) {
            sendBatch(batches[index], index, productIDs.length).done(function() {
                processBatches(index + 1);
            });
        } else {
            eraseCookie('deleted_products_count');
            $('.spinner').hide();
            successMessage.show();
            $('#download_as_csv').show();
        }
    }

    function sendBatch(batch, index, total) {
        return $.ajax({
            url: bulkProductRemover.ajaxurl,
            type: 'POST',
            data: {
                action: 'bulk_remove_delete',
                productIDs: batch,
                nonce: bulkProductRemover.nonce
            },
            success: function(response) {
                if (response.success) {
                    let deletedProducts = response.data.deleted_products;
                    deletedProducts.forEach(function(product) {
                        productDeletedResult.append('<li>' + product + '</li>');
                    });

                    let oldCount = parseInt(getCookie('deleted_products_count') || 0);
                    let newCount = oldCount + batch.length;
                    setCookie('deleted_products_count', newCount);
                    deletedProductsCount.text(newCount);

                    // Update progress bar
                    let progress = (newCount / total) * 100;
                    updateProgress(currentPercentage, progress);
                }
            },
            error: function(error) {
                console.error('Error processing batch:', error);
                processBatches(index);
            }
        });
    }

    // Start deletion process
    $('#start_delete_btn').click(function(e) {
        if (!confirm('Are you sure you want to delete these products?')) {
            e.preventDefault();
            return;
        }
        progressContainer.show();
        $(this).hide();
        processBatches(0);
        eraseCookie('deleted_products_count');
    });

    // Cancel deletion process
    $('#cancel_delete_btn').click(function() {
        location.reload();
    });

    // Download CSV report
    $('#download_as_csv').click(function(e) {
        e.preventDefault();
        let csvContent = "data:text/csv;charset=utf-8,ID,Product Name,Product URL,SKU\n";
        $('#product-deleted-result li').each(function() {
            csvContent += $(this).text() + "\n";
        });
        let encodedUri = encodeURI(csvContent);
        let link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "Deleted_Products.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
}); 