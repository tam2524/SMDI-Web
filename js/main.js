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

     async function includeHTML() {
    const includes = document.querySelectorAll('[data-include]');
    
    for (const include of includes) {
        const file = include.getAttribute('data-include');
        try {
            const response = await fetch(file);
            if (response.ok) {
                include.innerHTML = await response.text();
            } else {
                include.innerHTML = 'Page not found.';
            }
        } catch (error) {
            include.innerHTML = 'Error loading page.';
        }
    }
}

document.addEventListener('DOMContentLoaded', includeHTML);
    
//LTO REG INQUIRY
document.addEventListener("DOMContentLoaded", function() {
            const form = document.getElementById('inquiryForm');
            form.addEventListener('submit', function(event) {
                event.preventDefault();

                const formData = new FormData(form);

                fetch('api/register_details.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())  
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        const resultContainer = document.querySelector('.result');
                        resultContainer.innerHTML = '';

                        if (data.error) {
                            resultContainer.innerHTML = `<p>${data.error}</p>`;
                        } else {
                            resultContainer.innerHTML = `
                                <h4>Inquiry Results:</h4>
                                <p><strong>Date Reg:</strong> ${data.date_reg}</p>
                                <p><strong>Customer Name:</strong> ${data.full_name}</p>
                                <p><strong>MV File Number:</strong> ${data.mv_file_number}</p>
                                <p><strong>LTO Plate Number:</strong> ${data.lto_plate_number}</p>
                            `;
                        }
                    } catch (error) {
                        console.error('Parsing error:', error);
                        const resultContainer = document.querySelector('.result');
                        resultContainer.innerHTML = `<p>There was an error parsing the response.</p>`;
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    const resultContainer = document.querySelector('.result');
                    resultContainer.innerHTML = `<p>There was an error with the request.</p>`;
                });
            });
        });
//Motorcycle Inquiry
  document.addEventListener("DOMContentLoaded", function() {
            const mcbrand = document.getElementById("mcbrand");
            const mcmodel = document.getElementById("mcmodel");

            const models = {
                Suzuki: ["BURGMAN STREET 125 EX", "BURGMAN STREET","AVENIS", "SKYDRIVE CROSSOVER", "SKYDRIVE SPORT", "SMASH FI", "SMASH CARB", "RAIDER R150 FI", "RAIDER R150 CARB", "RAIDER J CROSSOVER", "V-STROM 250SX", "GIXXER250", "GIXXER SF250", "GIXXER FI", "GSX-8R", "GSX-S1000 GX", "GSX-8S", "V-STROM 800 DE", "V-STROM 1050 DE", "GSX-S1000GT", "GSX-S1000", "GSX-S750", "SV650", "BURGMAN 400", "HAYABUSA", "KATANA", "GSX-R 1000R", "GSX-R1000", "RM-Z450", "RM-Z250"],
                Yamaha: ["MIO SPORTY", "MIO I125", "MIO FAZZIO", "MIO SOULI125", "MIO GEAR", "MIO GRAVIS", "MIO AEROX", "MT-15", "MT-03", "XSR155", "NMAX", "XMAX", "SNIPER155", "YZF-R15M", "YZF-R3", "WR155R", "SEROW250", "XTZ125", "MT-10 SP", "MT-09", "MT-07", "XSR900", "XSR700", "BOLT R-SPEC", "SR400", "TMAX", "TMAX TECH MAX", "TRACER 9 GT", "SUPER TENERE ES", "TENERE 700", "YZF-R1M", "YZF-R7"],
                Kawasaki: ["NINJA H2 CARBON", "ZH2", "NINJA ZX-10RR", "NINJA ZX-10R", "NINJA NINJA ZX-6R", "NINJA ZX-25R SE", "NINJA ZX-4RR", "NINJA ZX-25R STANDARD", "NINJA ZX-4RR 40TH ANNIVERSARY EDITION","NINJA ZX-6R 40TH ANNIVERSARY EDITION","NINJA ZX-10R 40TH ANNIVERSARY EDITION", "NINJ 1000SX", "Z1000 R EDITION", "Z900 SE", "Z900 STANDARD", "NINJA 650", "Z650", "NINJA 400", "Z500", "Z500 SE", "KLX230", "KLX150", "KLX150S", "KLX300", "KLX300 SM", "KX450X", "KX250X", "KX450", "KX250", "KX65", "KLX300R", "KLX140", "KLX230R S", "BARAKO II", "BARAKO III", "ROUSER NS160FI", "RS200 WITH ABS", "NS200 FI WITH ABS", "NS125 FI", "CT100", "CT100B", "CT150", "CT125", "W800 CAFE", "W800 STREET", "VERSYS 1000 SE", "VERSYS 650", "VULCAN 1700 VAQUERO", "VULCAN 900 CUSTOM", "VULCAN S", "ELIMINATOR", "ELIMINATOR SE", "DOMINAR 400 UG2", "PULSAR NS250"],
                Honda: ["DIO", "BEAT (PLAYFUL)", "BEAT(PREMIUM)", "BEAT(LIMITED EDITION)", "CLICK125", "CLICK160", "CLICK125(SPECIAL EDITION)", "AIRBLADE 160", "PCX160-CBS", "PCX160-ABS", "ADV160", "CB150X", "CB150R", "XR150L", "CRF150L", "CRF300L", "CRF300 RALLY", "WAVE RSX (DRUM)", "WAVE RSX (DISC)", "XRM125DS", "XRM125 DSX", "XRM125 MOTARD", "RS125", "WINNER X (STANDARD)", "WINNER X(ABS PREMIUM)", "WINNER X(ABS RACING TYPE)", "TMX125 ALPHA", "TMX SUPREMO", "XL750 TRANSALP", "X-ADV", "NX500", "CRF1100L AFRICA TWIN", "CRF1100L ADVENTURE SPORTS", "CB500F", "CBR650R", "CBR1000RR-R FIREBLADE SP", "REBEL", "CL500", "REBEL 1100", "GOLD WING" ]
            };

            mcbrand.addEventListener("change", function() {
                const selectedBrand = mcbrand.value;
                const options = models[selectedBrand] || [];
                
                mcmodel.innerHTML = "<option value='' disabled selected>Select Model</option>"; 
                options.forEach(function(model) {
                    const option = document.createElement("option");
                    option.value = model;
                    option.textContent = model;
                    mcmodel.appendChild(option);
                });
            });
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


// LTO PLATE INQUIRY
document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById('inquiryForm');
    form.addEventListener('submit', function(event) {
        event.preventDefault();

        const formData = new FormData(form);

        fetch('api/lto_plate_inquiry.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())  
        .then(text => {
            try {
                const data = JSON.parse(text);
                const resultContainer = document.querySelector('.result');
                resultContainer.innerHTML = '';

                if (data.error) {
                    resultContainer.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                } else {
                    resultContainer.innerHTML = `
                      <div class="card">
    <div class="card-header bg-primary text-white">
        <h4 class="mb-0 text-white text-start">Inquiry Results</h4>
    </div>
    <div class="card-body text-start"> 
        <div class="row">
            <div class="col-md-6">
                <p><strong>Date Registered:</strong> ${data.date_reg || 'N/A'}</p>
                <p><strong>MV File Number:</strong> ${data.mv_file_number || 'N/A'}</p>
                <p><strong>Name:</strong> ${data.last_name || ''}, ${data.first_name || ''}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Branch:</strong> ${data.branch || 'N/A'}</p>
                <p><strong>Plate Number:</strong> ${data.plate_number || 'N/A'}</p>
                <p><strong>Remarks:</strong> ${data.remarks || 'N/A'}</p>
            </div>
        </div>
    </div>
</div>

                    `;
                }
            } catch (error) {
                console.error('Parsing error:', error);
                const resultContainer = document.querySelector('.result');
                resultContainer.innerHTML = `<div class="alert alert-danger">There was an error processing your request.</div>`;
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            const resultContainer = document.querySelector('.result');
            resultContainer.innerHTML = `<div class="alert alert-danger">There was an error connecting to the server.</div>`;
        });
    });
});