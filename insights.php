<?php
    session_start(); // Start the session.

    // Check if the user is logged in, if not then redirect to login page
    if(!isset($_SESSION["userID"])){
        header("location: index.html");
        exit;
    }

    $dbHost = 'localhost'; // or your database host
    $dbUsername = 'root'; // or your database username
    $dbPassword = ''; // or your database password
    $dbName = 'agile'; // your database name

    // Create connection
    $conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->close();
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Transactions</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"> <!-- Font Awesome CDN -->
        <style>
            #tooltip {
                background-color: #fff;
                border: 1px solid #ccc;
                border-radius: 5px;
                padding: 10px;
                position: absolute;
                text-align: center;
                visibility: hidden;
                opacity: 0;
                transition: opacity 0.3s;
                pointer-events: none;
            }

            .legend-container {
                display: grid;
                grid-template-columns: 1fr 1fr;
                grid-template-rows: repeat(4, 1fr);
                padding: 10px;
                gap: 20px;
            }

            .legend-item {
                display: flex;
                align-items: center;
                margin-bottom: 5px;
            }

            .legend-color-box {
                width: 20px;
                height: 20px;
                display: inline-block;
                margin-right: 5px;
            }

            .legend-text {
                font-size: 14px;
                color: #333;
            }

            .total-expenditure {
                margin-top: 20px;
                text-align: center;
                font-size: 20px;
                font-weight: bold;
            }

            .filter-container {
                margin-bottom: 20px;
            }

            .chart-container {
                background-color: #f2f2f2;
                border-radius: 25px;
                padding: 20px;
            }

            .chart-container h2 {
                text-align: center;
                margin-top: 0;
                padding-top: 20px;
            }

            .chart-wrapper {
                padding: 20px;
                display: flex;
                align-items: center;
                justify-content: space-around;
            }

            @media screen and (max-width: 900px) {
                .chart-wrapper {
                    flex-direction: column;
                }

                .legend-container {
                    margin-top: 20px;
                }
            }
        </style>
        <script src="https://d3js.org/d3.v6.min.js"></script>
    </head>
    <body>
        <button class="hamburger-menu">
            <i class="fas fa-bars"></i>
        </button>

        <div class="sidebar">
            <button class="close-sidebar">
                <i class="fas fa-times"></i>
            </button>
            <div class="sidebar-content">
                <a href="profile.php"><i class="fas fa-user-circle"></i> <span>Profile</span></a>
                <a href="transactions.php"><i class="fas fa-exchange-alt"></i> <span>Transactions</span></a>
                <a href="insights.php" class="active"><i class="fas fa-chart-bar"></i> <span>Insights</span></a>
                <a href="reminders.php"><i class="fas fa-bell"></i> <span>Reminders</span></a>
            </div>
            <div class="logout-section">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>

        <div class="content">
            <div class="title">
                <h1>Insights</h1>
            </div>
            <div class="content-container">
                <div class="main-content">
                    <div class="chart-container">
                        <h2>Expenditure</h2>
                        <div class="chart-wrapper">
                            <div class="legend-container">
                                <!-- Legend items will be dynamically inserted here by JavaScript -->
                            </div>
                        </div>
                        <div id="total-expenditure" class="total-expenditure">
                            <!-- The total expenditure will be inserted here -->
                        </div>
                    </div>
                    <div class="filter-container">
                        <input type="date" id="start-date" name="start">
                        <input type="date" id="end-date" name="end">
                        <button id="filter-btn">Filter</button>
                    </div>
                </div>
            </div>  
        </div>
        <div id="tooltip" style="opacity: 0; position: absolute; pointer-events: none; background-color: white; border: 1px solid; border-radius: 5px; padding: 10px; transition: opacity 0.3s;"></div>        
        <script src="script.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                // Function to fetch data and update the chart and legend
                const fetchDataAndUpdate = (startDate, endDate) => {
                    fetch(`data.php?start=${startDate}&end=${endDate}`)
                    .then(response => response.json())
                    .then(data => {
                        const pie = d3.pie().value(d => d.total_expenditure);
                        const arcData = pie(data);

                        const outerRadius = 150;
                        const innerRadius = 0;
                        const arc = d3.arc().innerRadius(innerRadius).outerRadius(outerRadius);

                        // Clear the previous content
                        d3.select('.chart-wrapper svg').remove();
                        const svg = d3.select('.chart-wrapper').insert('svg', ':first-child')
                            .attr('width', 300)
                            .attr('height', 300)
                            .append('g')
                            .attr('transform', 'translate(150,150)');

                        const tooltip = d3.select('#tooltip');

                        svg.selectAll('path')
                            .data(arcData)
                            .enter()
                            .append('path')
                            .attr('d', arc)
                            .attr('fill', d => d.data.colour)
                            .on('mouseover', function(event, d) {
                                tooltip.style('opacity', 1);
                                tooltip.html(`Category: ${d.data.title}<br>Expenditure: $${d.data.total_expenditure}`)
                                    .style('left', (event.pageX + 15) + 'px')
                                    .style('top', (event.pageY - 28) + 'px');
                            })
                            .on('mousemove', function(event) {
                                tooltip.style('left', (event.pageX + 15) + 'px')
                                    .style('top', (event.pageY - 28) + 'px');
                            })
                            .on('mouseout', function() {
                                tooltip.style('opacity', 0);
                            });

                        // Calculate the total expenditure
                        const totalExpenditure = data.reduce((acc, category) => acc + parseFloat(category.total_expenditure), 0);
                        const formattedTotal = new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP' }).format(totalExpenditure);

                        // Display the total expenditure
                        d3.select('#total-expenditure').text(`Total Expenditure: ${formattedTotal}`);
                        
                        // Create the legend
                        createLegend(data);
                    })
                    .catch(error => console.error('Error:', error));
                };

                // Function to create the legend
                function createLegend(data) {
                    const legendContainer = d3.select('.legend-container');
                    legendContainer.html(''); // Clear any existing legend items

                    data.forEach(category => {
                        const legendItem = legendContainer.append('div')
                            .attr('class', 'legend-item');

                        legendItem.append('div')
                            .attr('class', 'legend-color-box')
                            .style('background-color', category.colour);

                        legendItem.append('div')
                            .attr('class', 'legend-text')
                            .text(category.title);
                    });
                }

                // Event listener for the filter button
                document.getElementById('filter-btn').addEventListener('click', () => {
                    // Get the date values
                    const startDate = document.getElementById('start-date').value;
                    const endDate = document.getElementById('end-date').value;
                    // Fetch the data and update the chart
                    fetchDataAndUpdate(startDate, endDate);
                });

                // Initial fetch with no filters
                const initialStartDate = '1970-01-01'; // Adjust if needed
                const initialEndDate = new Date().toISOString().split('T')[0]; // Today's date
                fetchDataAndUpdate(initialStartDate, initialEndDate);
            });

        </script>
    </body>
    </html>
