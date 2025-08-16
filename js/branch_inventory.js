// Initialize the application
$(document).ready(function() {
    const map = initMap(currentBranch);
    
    // Event listeners
    $('#searchModelBtn').click(searchModels);
    $('#searchModel').keypress(function(e) {
        if (e.which === 13) searchModels();
    });

    // Initialize the map with branches that have inventory
function initMap(currentBranch) {
    const map = L.map('branchMap').setView([11.5852, 122.7511], 10);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    $.get('../api/inventory_management.php', {
        action: 'get_branches_with_inventory'
    }, function(response) {
        if (response.success) {
            const branchCoordinates = {
  'RXS-S': { lat: 11.581639063474135, lng: 122.75283046163139 },
  'RXS-H': { lat: 11.591933174094493, lng: 122.75177370058198 },
  'ANT-1': { lat: 10.747081312946916, lng: 121.94138590805788 },
  'ANT-2': { lat: 10.749653220828158, lng: 121.94142882340054 },
  'SDH': { lat: 10.697818450677735, lng: 122.56464019830032 },
  'SDS': { lat: 10.721591441858077, lng: 122.55598339171726 },
  'JAR-1': { lat: 10.746529482552543, lng: 122.56703172463938 },
  'JAR-2': { lat: 10.749878260560397, lng: 122.56812797163823 },
  'SKM': { lat: 11.726705198816557, lng: 122.36889838061255 },
  'SKS': { lat: 11.702856917692344, lng: 122.36675785507218 },
  'ALTA': "altavas", // (no coordinates provided yet)
  'EMAP': { lat: 11.581991439599044, lng: 122.75273929376398 },
  'CUL': { lat: 11.428798698065513, lng: 122.05695055376913 },
  'BAC': { lat: 10.670965032727254, lng: 122.95977720190973 },
  'PAS-1': { lat: 11.105396570048141, lng: 122.64601950262048 },
  'PAS-2': { lat: 11.106284551766606, lng: 122.64677038445016 },
  'BAL': { lat: 11.46865937405874, lng: 123.09560889637078 },
  'GUIM': { lat: 10.605846163901681, lng: 122.58799192677242 },
  'PEMDI': { lat: 10.65556975930108, lng: 122.93918296725195 },
  'EEM': { lat: 10.605758954854227, lng: 122.58813091469503 },
  'AJU': { lat: 11.179194176167435, lng: 123.01975649183555 },
  'BAIL': { lat: 11.450895697343983, lng: 122.82968507428964 },
  'MINDORO MB': { lat: 12.602606955880981, lng: 121.5037542414926 },
  'MINDORO 3S': { lat: 12.371133617009118, lng: 121.06330210820141 },
  'MANSALAY': { lat: 12.530846939769289, lng: 121.44707141396867 },
  'K-RIDERS': { lat: 11.626344148372608, lng: 122.73960109140822 },
  'IBAJAY': { lat: 11.815513408059678, lng: 122.15988390959608 },
  'NUMANCIA': { lat: 11.716374415728836, lng: 122.35946468260876 },
  'HEADOFFICE': { lat: 11.58156063320175, lng: 122.75277786727027 }
};


              response.data.forEach(branch => {
                if (branch.total_quantity > 0) {
                    const coord = branchCoordinates[branch.branch] || { lat: 11.5852, lng: 122.7511 };
                    const isCurrent = branch.branch === currentBranch;
                    
                    const marker = L.marker([coord.lat, coord.lng], {
                        icon: L.divIcon({
                            className: `branch-marker ${isCurrent ? 'current-branch' : ''}`,
                            html: branch.branch.substring(0, 2),
                            iconSize: [30, 30]
                        })
                    }).addTo(map);
                    
                    // THIS IS WHERE THE POPUP CODE GOES:
                    marker.bindPopup(`
                        <b>Branch ${branch.branch}</b><br>
                        <small>${branch.total_quantity} units (${branch.transferred_count} transferred)</small>
                    `);
                    
                    marker.on('click', function() {
                        loadBranchInventory(branch.branch);
                    });
                    }
                });
            }
        }, 'json');

        return map;
    }

    // Load inventory for a specific branch
    function loadBranchInventory(branchCode) {
        $('#branchInfo').html(`<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>`);
        $('#modelList').empty();
        
       $.get('../api/inventory_management.php', {
    action: 'get_branch_inventory',
    branch: branchCode,
    status: 'all' 
}, function(response) {
            if (response.success && response.data.length > 0) {
                $('#branchInfo').html(`
                    <h6>Branch: <strong>${branchCode}</strong></h6>
                    <p class="small">${response.data.length} units available</p>
                `);
                
                const modelGroups = groupByModel(response.data);
                let html = '';
                
                Object.keys(modelGroups).forEach(model => {
                    const items = modelGroups[model];
                    html += `
                        <div class="card mb-2 model-item" data-model="${model}">
                            <div class="card-body">
                                <h6 class="card-title">${model}</h6>
                                <p class="card-text small">
                                    ${items.length} available · 
                                    ${items[0].color} · 
                                    ${items[0].current_branch}
                                </p>
                            </div>
                        </div>
                    `;
                });
                
                $('#modelList').html(html);
                
                // Add click handler for model items
                $('.model-item').click(function() {
                    const model = $(this).data('model');
                    viewModelDetails(modelGroups[model][0].id);
                });
            } else {
                $('#branchInfo').html(`
                    <h6>Branch: <strong>${branchCode}</strong></h6>
                    <p class="text-muted">No inventory available</p>
                `);
                $('#modelList').html('<p class="text-muted">No models found</p>');
            }
        }, 'json');
    }

    // Group inventory items by model
    function groupByModel(items) {
        return items.reduce((groups, item) => {
            const key = `${item.brand} ${item.model}`;
            if (!groups[key]) groups[key] = [];
            groups[key].push(item);
            return groups;
        }, {});
    }

    // Search for models across all branches
    function searchModels() {
        const query = $('#searchModel').val().trim();
        if (query.length < 2) return;
        
        $('#modelList').html('<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>');
        
        $.get('../api/inventory_management.php', {
            action: 'search_inventory',
            query: query
        }, function(response) {
            if (response.success && response.data.length > 0) {
                const modelGroups = groupByModel(response.data);
                let html = '<h6>Search Results</h6>';
                
                Object.keys(modelGroups).forEach(model => {
                    const items = modelGroups[model];
                    html += `
                        <div class="card mb-2 model-item" data-model="${model}">
                            <div class="card-body">
                                <h6 class="card-title">${model}</h6>
                                <p class="card-text small">
                                    ${items.length} available · 
                                    ${items[0].color} · 
                                    ${items[0].current_branch}
                                </p>
                            </div>
                        </div>
                    `;
                });
                
                $('#modelList').html(html);
                $('#branchInfo').html('<h6>Search Results</h6>');
                
                // Add click handler for model items
                $('.model-item').click(function() {
                    const model = $(this).data('model');
                    viewModelDetails(modelGroups[model][0].id);
                });
            } else {
                $('#modelList').html('<p class="text-muted">No matching models found</p>');
                $('#branchInfo').html('<h6>Search Results</h6>');
            }
        }, 'json');
    }

    // View details of a specific motorcycle
    function viewModelDetails(id) {
    $('#motorcycleDetails').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>');
    
    $.get('../api/inventory_management.php', {
        action: 'get_motorcycle',
        id: id
    }, function(response) {
        if (response.success) {
            const item = response.data;
            let detailsHTML = `
                <h6>${item.brand} ${item.model}</h6>
                <p><strong>Color:</strong> ${item.color}</p>
                <p><strong>Current Branch:</strong> ${item.current_branch}</p>
                <p><strong>Status:</strong> <span class="badge ${getStatusClass(item.status)}">
                    ${item.status.charAt(0).toUpperCase() + item.status.slice(1)}
                </span></p>
                <hr>
                <p><strong>Engine #:</strong> ${item.engine_number}</p>
                <p><strong>Frame #:</strong> ${item.frame_number}</p>
                <p><strong>Date Delivered:</strong> ${item.date_delivered}</p>
            `;

            // Add transfer history if motorcycle is transferred
            if (item.status === 'transferred' && item.transfer_history && item.transfer_history.length > 0) {
                detailsHTML += `
                    <hr>
                    <h6>Transfer History</h6>
                    <div class="transfer-history">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                item.transfer_history.forEach(transfer => {
                    detailsHTML += `
                        <tr>
                            <td>${transfer.transfer_date}</td>
                            <td>${transfer.from_branch}</td>
                            <td>${transfer.to_branch}</td>
                            <td>${transfer.notes || '-'}</td>
                        </tr>
                    `;
                });

                detailsHTML += `
                            </tbody>
                        </table>
                    </div>
                `;
            }

            $('#motorcycleDetails').html(detailsHTML);
        } else {
            $('#motorcycleDetails').html('<p class="text-danger">Error loading details</p>');
        }
        
        new bootstrap.Modal(document.getElementById('detailsModal')).show();
    }, 'json');
}

    // Helper function for status badges
    function getStatusClass(status) {
        switch(status) {
            case 'available': return 'bg-success';
            case 'sold': return 'bg-danger';
            case 'transferred': return 'bg-warning text-dark';
            default: return 'bg-secondary';
        }
    }
});