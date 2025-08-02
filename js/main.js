(function ($) {
    "use strict";

    // Spinner
    var spinner = function () {
        setTimeout(function () {
            if ($('#spinner').length > 0) {
                $('#spinner').removeClass('show');
            }
        }, 1);
    };
    spinner(0);


    // Fixed Navbar
    $(window).scroll(function () {
        if ($(window).width() < 992) {
            if ($(this).scrollTop() > 55) {
                $('.fixed-top').addClass('shadow');
            } else {
                $('.fixed-top').removeClass('shadow');
            }
        } else {
            if ($(this).scrollTop() > 55) {
                $('.fixed-top').addClass('shadow').css('top', -55);
            } else {
                $('.fixed-top').removeClass('shadow').css('top', 0);
            }
        }
    });


    // Back to top button
    $(window).scroll(function () {
        if ($(this).scrollTop() > 300) {
            $('.back-to-top').fadeIn('slow');
        } else {
            $('.back-to-top').fadeOut('slow');
        }
    });
    $('.back-to-top').click(function () {
        $('html, body').animate({ scrollTop: 0 }, 1500, 'easeInOutExpo');
        return false;
    });


    // Testimonial carousel
    $(".testimonial-carousel").owlCarousel({
        autoplay: true,
        smartSpeed: 2000,
        center: false,
        dots: true,
        loop: true,
        margin: 25,
        nav: true,
        navText: [
            '<i class="bi bi-arrow-left"></i>',
            '<i class="bi bi-arrow-right"></i>'
        ],
        responsiveClass: true,
        responsive: {
            0: {
                items: 1
            },
            576: {
                items: 1
            },
            768: {
                items: 1
            },
            992: {
                items: 2
            },
            1200: {
                items: 2
            }
        }
    });


    // vegetable carousel
    $(".vegetable-carousel").owlCarousel({
        autoplay: true,
        smartSpeed: 1500,
        center: false,
        dots: true,
        loop: true,
        margin: 25,
        nav: true,
        navText: [
            '<i class="bi bi-arrow-left"></i>',
            '<i class="bi bi-arrow-right"></i>'
        ],
        responsiveClass: true,
        responsive: {
            0: {
                items: 1
            },
            576: {
                items: 1
            },
            768: {
                items: 2
            },
            992: {
                items: 3
            },
            1200: {
                items: 4
            }
        }
    });


    // Modal Video
    $(document).ready(function () {
        var $videoSrc;
        $('.btn-play').click(function () {
            $videoSrc = $(this).data("src");
        });
        console.log($videoSrc);

        $('#videoModal').on('shown.bs.modal', function (e) {
            $("#video").attr('src', $videoSrc + "?autoplay=1&amp;modestbranding=1&amp;showinfo=0");
        })

        $('#videoModal').on('hide.bs.modal', function (e) {
            $("#video").attr('src', $videoSrc);
        })
    });



    // Product Quantity
    $('.quantity button').on('click', function () {
        var button = $(this);
        var oldValue = button.parent().parent().find('input').val();
        if (button.hasClass('btn-plus')) {
            var newVal = parseFloat(oldValue) + 1;
        } else {
            if (oldValue > 0) {
                var newVal = parseFloat(oldValue) - 1;
            } else {
                newVal = 0;
            }
        }
        button.parent().parent().find('input').val(newVal);
    });

})(jQuery);

//Pop up window for Map
function openMapPopup(event) {
    event.preventDefault();
    var url = event.target.href;
    var windowName = 'mapPopup';
    var windowFeatures = 'width=900,height=800,resizable,scrollbars=yes';

    window.open(url, windowName, windowFeatures);
}

//Send SMS
function sendMessage() {
    fetch('api/inquiry.php')
        .then(response => response.text())
        .then(data => {
            document.getElementById('result').innerText = data;
        })
        .catch(error => {
            document.getElementById('result').innerText = 'Error: ' + error;
        });
}

//Current Date
document.addEventListener("DOMContentLoaded", function() {
    const dateElement = document.getElementById("currentDate");
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    const today = new Date().toLocaleDateString(undefined, options);
    dateElement.textContent = today;
});
// About Transition
    function isElementInViewport(el) {
        const rect = el.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }

    function onScroll() {
        const aboutSections = document.querySelectorAll('.about, .about1, .about2, .about3');
        aboutSections.forEach(section => {
            if (isElementInViewport(section)) {
                section.classList.add('visible');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        window.addEventListener('scroll', onScroll);
        onScroll(); 
    });
    
//Liaison DB
$(document).ready(function() {
    let RecordIdToDelete = null;

    function loadRecords(query = '', category = '') {
        $.ajax({
            url: 'api/fetch_Records.php',
            method: 'GET',
            data: { query: query, category: category },
            success: function(data) {
                $('#RecordTableBody').html(data);
            },
            error: function(xhr, status, error) {
                console.error("Error loading Records:", error);
            }
        });
    }


    loadRecords();

    $('#searchInput').on('input', function() {
        const query = $(this).val();
        loadRecords(query, $('#categoryFilter').val());
    });

 
    $('#addRecordForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'api/add_Record.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    loadRecords();
                    $('#addModal').modal('hide');
                    showSuccessModal(response.message);
                } else {
                    showErrorModal(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error("Error adding Record:", error);
            }
        });
    });

    $('#RecordTableBody').on('click', '.edit-button', function() {
        let row = $(this).closest('tr');
        let id = row.data('id');
        $.ajax({
            url: 'api/get_Record.php',
            method: 'GET',
            data: { id: id },
            success: function(data) {
                try {
                    let Record = JSON.parse(data);
                    $('#editRecordId').val(Record.Record_id);
                    $('#editUnspscCode').val(Record.unspsc_code);
                    $('#editRecordCode').val(Record.Record_code);
                    $('#editRecordDesc').val(Record.Record_desc);
                    $('#editRecordCategory').val(Record.Record_category);
                    $('#editGrossPrice').val(Record.gross_price);
                    $('#editNetPrice').val(Record.net_price);
                    $('#editRecordModal').modal('show');
                } catch (e) {
                    console.error("Error parsing Record data:", e);
                }
            },
            error: function(xhr, status, error) {
                console.error("Error fetching Record:", error);
            }
        });
    });

    $('#editRecordForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'api/edit_Record.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    loadRecords();
                    $('#editRecordModal').modal('hide');
                    showSuccessModal(response.message);
                } else {
                    showErrorModal(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error("Error editing Record:", error);
            }
        });
    });

    $('#RecordTableBody').on('click', '.delete-button', function() {
        let row = $(this).closest('tr');
        RecordIdToDelete = row.data('id');
        $('#confirmationModal').modal('show');
    });

    $('#confirmDeleteBtn').click(function() {
        if (RecordIdToDelete !== null) {
            $.ajax({
                url: 'api/delete_Record.php',
                method: 'POST',
                data: { id: RecordIdToDelete },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        loadRecords();
                        showSuccessModal(response.message);
                    } else {
                        showErrorModal(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error deleting Record:", error);
                },
                complete: function() {
                    $('#confirmationModal').modal('hide');
                    RecordIdToDelete = null;
                }
            });
        }
    });

    function showSuccessModal(message) {
        $('#successModal .modal-body').text(message);
        $('#successModal').modal('show');
    }

    function showErrorModal(message) {
        $('#errorModal .modal-body').text(message);
        $('#errorModal').modal('show');
    }

    $('#printButton').on('click', function() {
        printTable();
    });

    document.getElementById('printButton').addEventListener('click', function () {
         document.querySelectorAll('#RecordTable th:last-child, #RecordTable td:last-child').forEach(function (element) {
            element.style.display = 'none';
        });
    
        
        printJS({
            printable: 'RecordTable',
            type: 'html',
            style: `
            
            table {
                font-size: 10px;
                width: 100%;
                border-collapse: collapse;
            }
            th, td {
                font-size: 10px;
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #f2f2f2;
                font-weight: bold;
            }
            td {
                border-bottom: 1px dotted #ddd;
            }
            .modal-footer, .modal-header {
                display: none;
            }
            @media print {
                body {
                    margin: 0;
                    padding: 0;
                }
                .no-print {
                    display: none !important;
                }
                .modal-body {
                    padding: 0;
                    margin: 0;
                }
                .modal-content {
                    border: none;
                    box-shadow: none;
                }
            }
            @page {
                margin: 1cm;
                @top-left {
                    content: none;
                }
                @top-right {
                    content: none;
                }
                @bottom-left {
                    content: none;
                }
                @bottom-right {
                    content: none;
                }
            }
        `
    });
        setTimeout(function () {
            document.querySelectorAll('#RecordTable th:last-child, #RecordTable td:last-child').forEach(function (element) {
                element.style.display = '';
            });
        }, 1000); 
    });
});
