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
    $userId = $_SESSION["userID"];

    // Create connection
    $conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Prepare the SQL query to fetch categories and custom categories
    $sqlCategories = "
        SELECT title FROM Category
        UNION ALL
        SELECT title FROM CustomCategory WHERE userID = ?
    ";
    $stmt = $conn->prepare($sqlCategories);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $resultCategories = $stmt->get_result();

    $categories = [];
    while($row = $resultCategories->fetch_assoc()) {
        $categories[] = $row;
    }

    $stmt->close();
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
                z-index: 999;
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
                background-color: #424242;
                width: fit-content;
                margin: 20px auto;
                padding: 10px 20px;
                border-radius: 25px;
                color: white;
            }

            .filter-container input {
                margin-right: 10px;
                font-size: 20px;
                border-radius: 25px;
                padding: 5px;
            }

            .filter-container button {
                font-size: 20px;
                border-radius: 25px;
                padding: 5px 10px;
                border: none;
                cursor: pointer;
            }

            #filter-btn {
                background-color: #3a61f2;
                color: white;
                padding: 5px 20px;
                margin-right: 5px;
            }

            #reset-btn {
                background-color: #f44336;
                color: white;
            }

            .chart-container {
                background-color: #f2f2f2;
                border-radius: 25px;
                padding: 20px;
            }

            .chart-container:not(:last-child) {
                margin-bottom: 40px;
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

            #total-expenditure-small {
                display: none;
            }

            #lineGraph-container svg {
                min-width: 960px; /* Set to the natural width of the SVG */
                width: 100%;
                height: 500px;
            }

            #barGraph-container svg {
                min-width: 960px; /* Set to the natural width of the SVG */
                width: 100%;
                height: 520px;
            }

            #category-selector {
                font-size: 20px;
                padding: 5px;
                border-radius: 25px;
                margin-right: 10px;
            }

            @media screen and (max-width: 1250px) {
                .chart-wrapper {
                    flex-direction: column;
                }

                .legend-container {
                    margin-top: 20px;
                }

                #total-expenditure-large {
                    display: none;
                }

                #total-expenditure-small {
                    display: block;
                }
            }

            @media screen and (max-width: 1100px) {
                .chart-wrapper {
                    flex-direction: row;
                }

                .legend-container {
                    margin-top: 0px;
                }

                #total-expenditure-large {
                    display: block;
                }

                #total-expenditure-small {
                    display: none;
                }
            }

            @media screen and (max-width: 1000px) {
                .chart-wrapper {
                    flex-direction: column;
                }

                .legend-container {
                    margin-top: 20px;
                }

                #total-expenditure-large {
                    display: none;
                }

                #total-expenditure-small {
                    display: block;
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
                            <div id="total-expenditure-small" class="total-expenditure">
                                <!-- The total expenditure will be inserted here -->
                            </div>
                            <div class="legend-container">
                                <!-- Legend items will be dynamically inserted here by JavaScript -->
                            </div>
                        </div>
                        <div id="total-expenditure-large" class="total-expenditure">
                            <!-- The total expenditure will be inserted here -->
                        </div>
                        <div class="filter-container">
                            <label for="start-date">From:</label>
                            <input type="date" id="start-date" name="start">
                            <label for="end-date">To:</label>
                            <input type="date" id="end-date" name="end">
                            <button id="filter-btn">Filter</button>
                            <button id="reset-btn">Reset</button>
                        </div>
                    </div>

                    <div class="chart-container">
                        <div class="line-chart-container">
                            <h2>Monthly Net Spend</h2>
                            <div id="lineGraph-container" style="overflow-x: auto; width: 100%;">
                                <svg id="lineGraph"></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Bar Graph Container -->
                    <div class="chart-container">
                        <h2>Monthly Category Overview</h2>
                        <div id="barGraph-container" style="overflow-x: auto; width: 100%;">
                            <svg id="barGraph"></svg>
                        </div>
                        <div class="filter-container">
                            <select id="category-selector">
                                <!-- Dynamically populated options -->
                                <?php foreach($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['title']); ?>">
                                        <?php echo htmlspecialchars($category['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button id="category-btn" onclick="handleCategoryChange()">Show Statistics</button>
                        </div>
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
                                tooltip.style('visibility', 'visible');
                                tooltip.html(`Category: ${d.data.title}<br>Expenditure: £${d.data.total_expenditure}`);
                                adjustTooltipPosition(event);
                            })
                            .on('mousemove', adjustTooltipPosition)
                            .on('mouseout', function() {
                                tooltip.style('opacity', 0);
                                tooltip.style('visibility', 'hidden');
                            });

                        function adjustTooltipPosition(event) {
                            let x = event.pageX + 15;
                            let y = event.pageY - 28;
                            const tooltipWidth = tooltip.node().offsetWidth;
                            const tooltipHeight = tooltip.node().offsetHeight;
                            const windowWidth = window.innerWidth;
                            const windowHeight = window.innerHeight;

                            // Adjust horizontal position to avoid going offscreen
                            if (x + tooltipWidth > windowWidth) {
                                x = windowWidth - tooltipWidth - 20; // 20px padding from the edge
                            }

                            // Adjust vertical position to avoid going offscreen
                            if (y + tooltipHeight > windowHeight) {
                                y = windowHeight - tooltipHeight - 20; // 20px padding from the bottom
                            }

                            // Apply the position adjustments
                            tooltip.style('left', `${x}px`);
                            tooltip.style('top', `${y}px`);
                        }


                        // Calculate the total expenditure
                        const totalExpenditure = data.reduce((acc, category) => acc + parseFloat(category.total_expenditure), 0);
                        const formattedTotal = new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP' }).format(totalExpenditure);

                        // Display the total expenditure
                        d3.select('#total-expenditure-large').text(`Total Expenditure: ${formattedTotal}`);
                        d3.select('#total-expenditure-small').text(`Total Expenditure: ${formattedTotal}`);
                        
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

                // Event listener for the reset button
                document.getElementById('reset-btn').addEventListener('click', () => {
                    // Clear the date inputs
                    document.getElementById('start-date').value = '';
                    document.getElementById('end-date').value = '';
                    
                    // Fetch the data and update the chart without filters
                    const initialStartDate = '1970-01-01'; // Adjust if needed
                    const initialEndDate = new Date().toISOString().split('T')[0]; // Today's date
                    fetchDataAndUpdate(initialStartDate, initialEndDate);
                });

                // Initial fetch with no filters
                const initialStartDate = '1970-01-01'; // Adjust if needed
                const initialEndDate = new Date().toISOString().split('T')[0]; // Today's date
                fetchDataAndUpdate(initialStartDate, initialEndDate);

                // Line chart
                const drawLineGraph = (data) => {
                    const svg = d3.select("#lineGraph");
                    const svgWidth = 960, svgHeight = 500;
                    const margin = {top: 20, right: 20, bottom: 30, left: 50},
                        width = svgWidth - margin.left - margin.right,
                        height = svgHeight - margin.top - margin.bottom;
                    const g = svg.append("g").attr("transform", `translate(${margin.left},${margin.top})`);

                    // Find the minimum and maximum net spend to set the y-axis domain
                    const netSpendMin = d3.min(data, d => d.netSpend);
                    const netSpendMax = d3.max(data, d => d.netSpend);

                    const x = d3.scaleTime()
                        .rangeRound([0, width])
                        .domain(d3.extent(data, d => d.month));

                    const y = d3.scaleLinear()
                        .rangeRound([height, 0])
                        .domain([netSpendMin, netSpendMax]);

                    // Calculate where the x-axis should be positioned based on the y-scale
                    const xAxisTranslate = y(0) < 0 ? 0 : y(0) > height ? height : y(0);

                    g.append("g")
                        .attr("transform", `translate(0,${xAxisTranslate})`)
                        .call(d3.axisBottom(x));

                    g.append("g")
                        .call(d3.axisLeft(y))
                        .append("text")
                        .attr("fill", "#000")
                        .attr("transform", "rotate(-90)")
                        .attr("y", 6)
                        .attr("dy", "0.71em")
                        .attr("text-anchor", "end")
                        .text("Net Spend (£)");

                    const line = d3.line()
                        .x(d => x(d.month))
                        .y(d => y(d.netSpend));

                    g.append("path")
                        .datum(data)
                        .attr("fill", "none")
                        .attr("stroke", "steelblue")
                        .attr("stroke-linejoin", "round")
                        .attr("stroke-linecap", "round")
                        .attr("stroke-width", 1.5)
                        .attr("d", line);
                };

                fetch('fetch_financial_data.php')
                .then(response => response.json())
                .then(data => {
                    // Process and format the data if necessary
                    const formattedData = data.map(item => ({
                        ...item,
                        month: new Date(item.year, item.month - 1, 1)
                    }));

                    // Draw the line graph with the formatted data
                    drawLineGraph(formattedData);
                })
                .catch(error => console.error('Error:', error));

                // Function to handle the category selection and button click
                function handleCategoryChange() {
                    // Get the selected category
                    const selectedCategory = document.getElementById('category-selector').value;

                    // Fetch and display the bar graph for the selected category
                    updateBarGraph(selectedCategory);
                }

                // Update bar graph based on selected category
                function updateBarGraph(category) {
                    fetch(`fetch_categories.php?category=${encodeURIComponent(category)}`)
                        .then(response => response.json())
                        .then(data => {
                            drawBarGraph(data);
                        })
                        .catch(error => console.error('Error fetching category data:', error));
                }

                document.getElementById('category-btn').addEventListener('click', handleCategoryChange);

                // Draw bar graph
                function drawBarGraph(data) {
                    // Clear the previous bar graph
                    d3.select('#barGraph').selectAll("*").remove();
                    const svg = d3.select("#barGraph");
                    const svgWidth = 960, svgHeight = 500;
                    const margin = {top: 20, right: 20, bottom: 30, left: 50},
                        width = svgWidth - margin.left - margin.right,
                        height = svgHeight - margin.top - margin.bottom;
                    const g = svg.append("g").attr("transform", `translate(${margin.left},${margin.top})`);

                    const x = d3.scaleBand().rangeRound([0, width]).padding(0.1);
                    const y = d3.scaleLinear().rangeRound([height, 0]);

                    // Set up your domains based on the data
                    x.domain(data.map(d => `${d.year}-${d.month}`));
                    y.domain([0, d3.max(data, d => Math.max(d.totalIncome, d.totalOutcome))]);

                    // Append g element for the bar graph
                    g.append("g")
                        .attr("transform", `translate(0,${height})`)
                        .call(d3.axisBottom(x))
                        .selectAll("text")
                        .style("text-anchor", "end")
                        .attr("dx", "-.8em")
                        .attr("dy", ".15em")
                        .attr("transform", "rotate(-65)");

                    g.append("g")
                        .call(d3.axisLeft(y).ticks(10, "$"));

                    // Create bars for total ins
                    g.selectAll(".bar.income")
                        .data(data)
                        .enter()
                        .append("rect")
                        .attr("class", "bar income")
                        .attr("x", d => x(`${d.year}-${d.month}`))
                        .attr("y", d => y(d.totalIncome))
                        .attr("width", x.bandwidth() / 2)
                        .attr("height", d => height - y(d.totalIncome))
                        .attr("fill", "green");

                    // Create bars for total outs
                    g.selectAll(".bar.outcome")
                        .data(data)
                        .enter()
                        .append("rect")
                        .attr("class", "bar outcome")
                        .attr("x", d => x(`${d.year}-${d.month}`) + x.bandwidth() / 2)
                        .attr("y", d => y(d.totalOutcome))
                        .attr("width", x.bandwidth() / 2)
                        .attr("height", d => height - y(d.totalOutcome))
                        .attr("fill", "red");
                    }

                // Call updateBarGraph once to show initial data
                updateBarGraph();
            });
        </script>
    </body>
    </html>
