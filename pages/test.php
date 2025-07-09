<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fetch Roles</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- jQuery CDN -->
</head>
<body>

<div id="roles-list">
    <!-- The list of roles will be displayed here -->
</div>

<script>
$(document).ready(function() {
    // Function to fetch roles
    function fetchRoles(c_id) {
        $.ajax({
            url: '../backend/api/get_roles.php',  // Ensure this path is correct
            type: 'GET',
            data: { c_id: c_id }, // Send the company ID
            success: function(response) {
                console.log(response); // Add this line to log the response
                if (response.success) {
                    displayRoles(response.roles);
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error in fetching roles.');
            }
        });
    }

    // Function to display roles on the page
    function displayRoles(roles) {
        let htmlContent = '';
        if (roles.length > 0) {
            roles.forEach(function(role) {
                htmlContent += `
                    <div class="role-item">
                        <h3>${role.name}</h3>
                        <p>${role.description}</p>
                        <small>Permissions: ${role.permissions}</small>
                    </div>
                    <hr>
                `;
            });
        } else {
            htmlContent = '<p>No roles found for this company.</p>';
        }

        // Insert the roles into the HTML container
        $('#roles-list').html(htmlContent);
    }

    // Example: Fetch roles for company with ID 1 (you can change this based on session or user selection)
    fetchRoles(1);  // Replace with the actual company ID dynamically
});

</script>

</body>
</html>
