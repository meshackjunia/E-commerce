document.addEventListener('DOMContentLoaded', function() {
    // Handle search form submission
    const searchForm = document.getElementById('search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const query = document.getElementById('search-input').value;
            const departments = [];
            
            // Get selected departments
            document.querySelectorAll('.search-options input[type="checkbox"]:checked').forEach(checkbox => {
                departments.push(checkbox.name);
            });
            
            // Perform search (in a real app, this would be an API call)
            performSearch(query, departments);
        });
    }
    
    function performSearch(query, departments) {
        console.log(`Searching for "${query}" in departments: ${departments.join(', ')}`);
        // In a real implementation, you would:
        // 1. Make an AJAX request to your search API
        // 2. Display the results
        // 3. Handle pagination
    }
});