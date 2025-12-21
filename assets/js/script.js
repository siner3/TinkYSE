// Toggle filter collapse/expand
function toggleFilter(titleElement) {
    const filterGroup = titleElement.closest('.filter-group');
    filterGroup.classList.toggle('collapsed');
}

// Active filters storage
let activeFilters = {
    gender: [],
    style: [],
    designer: [],
    material: [],
    aesthetics: []
};

// Apply filters when button is clicked
document.getElementById('applyFilterBtn').addEventListener('click', function() {
    // Clear current active filters
    activeFilters = {
        gender: [],
        style: [],
        designer: [],
        material: [],
        aesthetics: []
    };

    // Collect all checked checkboxes
    const checkboxes = document.querySelectorAll('#filterForm input[type="checkbox"]:checked');
    
    checkboxes.forEach(checkbox => {
        const filterType = checkbox.name;
        const filterValue = checkbox.value;
        
        if (activeFilters[filterType]) {
            activeFilters[filterType].push(filterValue);
        }
    });

    // Update the active filters display
    updateActiveFiltersDisplay();
    
    // Apply the filters to products (you can add your filtering logic here)
    applyFiltersToProducts();
});

// Update active filters pills display
function updateActiveFiltersDisplay() {
    const activeFiltersContainer = document.getElementById('activeFilters');
    activeFiltersContainer.innerHTML = '';

    // Loop through each filter type
    for (const [filterType, values] of Object.entries(activeFilters)) {
        values.forEach(value => {
            const pill = document.createElement('div');
            pill.className = 'filter-pill';
            pill.innerHTML = `
                ${value}
                <i class="fas fa-times" onclick="removeFilter('${filterType}', '${value}')"></i>
            `;
            activeFiltersContainer.appendChild(pill);
        });
    }
}

// Remove a specific filter
function removeFilter(filterType, filterValue) {
    // Remove from active filters
    activeFilters[filterType] = activeFilters[filterType].filter(v => v !== filterValue);
    
    // Uncheck the corresponding checkbox
    const checkbox = document.querySelector(`input[name="${filterType}"][value="${filterValue}"]`);
    if (checkbox) {
        checkbox.checked = false;
    }
    
    // Update display
    updateActiveFiltersDisplay();
    
    // Re-apply filters
    applyFiltersToProducts();
}

// Apply filters to products (placeholder - you can customize this)
function applyFiltersToProducts() {
    console.log('Applying filters:', activeFilters);
    
    // Here you would filter your products based on activeFilters
    // For example:
    // const products = document.querySelectorAll('.product-card');
    // products.forEach(product => {
    //     // Check if product matches active filters
    //     // Show or hide accordingly
    // });
    
    // You can also send an AJAX request to filter server-side
}

// Smooth scroll functionality
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', function(e) {
        // Remove active class from all links
        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        // Add active class to clicked link
        this.classList.add('active');
    });
});

// Sort dropdown change handler
document.querySelector('.sort-dropdown').addEventListener('change', function() {
    const sortValue = this.value;
    console.log('Sorting by:', sortValue);
    
    // Add your sorting logic here
    // For example, you could sort the product grid based on the selected option
});

// Optional: Detect checkbox changes in real-time (without Apply button)
// Uncomment if you want instant filtering
/*
document.querySelectorAll('#filterForm input[type="checkbox"]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        // Get the filter type
        const filterType = this.name;
        const filterValue = this.value;
        
        if (this.checked) {
            // Add to active filters
            if (!activeFilters[filterType].includes(filterValue)) {
                activeFilters[filterType].push(filterValue);
            }
        } else {
            // Remove from active filters
            activeFilters[filterType] = activeFilters[filterType].filter(v => v !== filterValue);
        }
        
        updateActiveFiltersDisplay();
        applyFiltersToProducts();
    });
});
*/

// Initialize - collapse all filters except the first one (optional)
document.addEventListener('DOMContentLoaded', function() {
    const filterGroups = document.querySelectorAll('.filter-group');
    
    // Optionally collapse all filters on load
    // filterGroups.forEach((group, index) => {
    //     if (index > 0) {
    //         group.classList.add('collapsed');
    //     }
    // });
});

// Search functionality
const searchInput = document.querySelector('.search-bar input');
if (searchInput) {
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const searchTerm = this.value;
            console.log('Searching for:', searchTerm);
            // Add your search logic here
        }
    });
}

// Search icon click
const searchIcon = document.querySelector('.search-bar i');
if (searchIcon) {
    searchIcon.addEventListener('click', function() {
        const searchTerm = searchInput.value;
        console.log('Searching for:', searchTerm);
        // Add your search logic here
    });
}