<?php
session_start();
include "../config/conn.php";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['c_id'])) {
    header("Location: domain.php");
    exit();
}

echo $_SESSION['user_id'] ;
echo $_SESSION['c_id'] ;

?>



<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include "components/head.php";
  ?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

</head>

<body class="g-sidenav-show  bg-gray-100">

    <?php
    include "components/sidebar.php";
  ?>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">

        <?php
          include "components/navbar.php";
        ?>
        <div class="container-fluid py-2">
            <div class="row">
                <div class="ms-3">
                    <h3 class="mb-0 h4 font-weight-bolder">Dashboard</h3>
                    <p class="mb-4">
                        Check the Documents.
                    </p>
                </div>
                <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                    <div class="card">
                        <div class="card-header p-2 ps-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="text-sm mb-0 text-capitalize">Total Documents</p>
                                    <h4 class="mb-0">533</h4>
                                </div>
                                <div
                                    class="icon icon-md icon-shape bg-gradient-info   shadow-dark shadow text-center border-radius-lg">
                                    <i class="material-symbols-rounded opacity-10">weekend</i>
                                </div>
                            </div>
                        </div>
                        <hr class="dark horizontal my-0">
                        <div class="card-footer p-2 ps-3">
                            <p class="mb-0 text-sm"><span class="text-success font-weight-bolder">+55 </span>than last
                                week</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                    <div class="card">
                        <div class="card-header p-2 ps-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="text-sm mb-0 text-capitalize">Total Folders</p>
                                    <h4 class="mb-0">2300</h4>
                                </div>
                                <div
                                    class="icon icon-md icon-shape bg-gradient-info shadow-dark shadow text-center border-radius-lg">
                                    <i class="material-symbols-rounded opacity-10">leaderboard</i>

                                </div>
                            </div>
                        </div>
                        <hr class="dark horizontal my-0">
                        <div class="card-footer p-2 ps-3">
                            <p class="mb-0 text-sm"><span class="text-success font-weight-bolder">+3 </span>than last
                                month</p>
                        </div>
                    </div>
                </div>
                <?php
                    include "../config/conn.php";

                    $totalUsers = 0;
                    if (isset($_SESSION['c_id'])) {
                        $companyId = $_SESSION['c_id'];
                        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM users WHERE c_id = ?");
                        $stmt->bind_param("i", $companyId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($row = $result->fetch_assoc()) {
                            $totalUsers = $row['total'];
                        }
                        $stmt->close();
                    }
                ?>
                <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                    <div class="card">
                        <div class="card-header p-2 ps-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="text-sm mb-0 text-capitalize">Total Users</p>
                                    <h4 class="mb-0"><?= number_format($totalUsers) ?></h4>
                                </div>
                                <div
                                    class="icon icon-md icon-shape bg-gradient-info shadow-dark shadow text-center border-radius-lg">
                                    <i class="material-symbols-rounded opacity-10">person</i>
                                </div>
                            </div>
                        </div>
                        <hr class="dark horizontal my-0">
                        <div class="card-footer p-2 ps-3">
                            <p class="mb-0 text-sm"><span class="text-success font-weight-bolder">+2 </span>than
                                yesterday</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6">
                    <div class="card">
                        <div class="card-header p-2 ps-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="text-sm mb-0 text-capitalize">Shared Documents</p>
                                    <h4 class="mb-0">103</h4>
                                </div>
                                <div
                                    class="icon icon-md icon-shape bg-gradient-info shadow-dark shadow text-center border-radius-lg">
                                    <i class="material-symbols-rounded opacity-10">weekend</i>
                                </div>
                            </div>
                        </div>
                        <hr class="dark horizontal my-0">
                        <div class="card-footer p-2 ps-3">
                            <p class="mb-0 text-sm"><span class="text-success font-weight-bolder">+5 </span>than
                                yesterday</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4 col-md-6 mt-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="mb-0 ">Storage Usage Status</h6>
                            <p class="text-sm ">Last Campaign Performance</p>
                            <div class="pe-2">
                                <div class="chart d-flex justify-content-center" style="height: 170px;">
                                    <canvas id="chart-storage" class="chart-canvas" height="170"></canvas>
                                </div>
                            </div>
                            <hr class="dark horizontal">
                            <div class="d-flex ">
                                <i class="material-symbols-rounded text-sm my-auto me-1">schedule</i>
                                <p class="mb-0 text-sm"> 65 GB/100 GB Used Space</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mt-4 mb-4">
                    <div class="card ">
                        <div class="card-body">
                            <h6 class="mb-0 "> Daily Uploads </h6>
                            <p class="text-sm "> <span class="font-weight-bolder">+15</span> Documents Uploaded today.
                            </p>
                            <div class="pe-2">
                                <div class="chart">
                                    <canvas id="chart-line" class="chart-canvas" height="170"></canvas>
                                </div>
                            </div>
                            <hr class="dark horizontal">
                            <div class="d-flex ">
                                <i class="material-symbols-rounded text-sm my-auto me-1">schedule</i>
                                <p class="mb-0 text-sm"> updated 4 min ago </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mt-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="mb-0">Document Types</h6>
                            <p class="text-sm">Overview of uploaded files by type</p>
                            <div class="pe-2">
                                <div class="chart">
                                    <canvas id="chart-bars" class="chart-canvas" height="170"></canvas>
                                </div>
                            </div>
                            <hr class="dark horizontal">
                            <div class="d-flex">
                                <i class="material-symbols-rounded text-sm my-auto me-1">upload</i>
                                <p class="mb-0 text-sm"> Last document uploaded 2 days ago </p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>


        <div class="row mb-4">
            <!-- Recent -->
            <div class="col-lg-8 col-md-6 mb-md-0 mb-4">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="row">
                            <div class="col-lg-6 col-7">
                                <h6>Recent Documents</h6>
                                <p class="text-sm mb-0">
                                    <i class="fa fa-folder-open text-info" aria-hidden="true"></i>
                                    <span class="font-weight-bold ms-1">15 uploaded</span> this week
                                </p>
                            </div>
                            <div class="col-lg-6 col-5 my-auto text-end">
                                <div class="dropdown float-lg-end pe-4">
                                    <a class="cursor-pointer" id="dropdownTable" data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                        <i class="fa fa-ellipsis-v text-secondary"></i>
                                    </a>
                                    <ul class="dropdown-menu px-2 py-3 ms-sm-n4 ms-n5" aria-labelledby="dropdownTable">
                                        <li><a class="dropdown-item border-radius-md" href="#">Upload New</a></li>
                                        <li><a class="dropdown-item border-radius-md" href="#">Manage Folders</a></li>
                                        <li><a class="dropdown-item border-radius-md" href="#">Settings</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activites -->
                    <div class="card-body px-0 pb-2">
                        <div class="table-responsive">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Name</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Folder</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Uploaded By</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Date</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Size</th>
                                        <th
                                            class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">
                                            Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Row 1 -->
                                    <tr>
                                        <td>
                                            <h6 class="mb-0 text-sm">Annual Report.pdf</h6>
                                        </td>
                                        <td><span class="text-xs font-weight-bold">Finance</span></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="../assets/img/team-1.jpg"
                                                    class="avatar avatar-xs rounded-circle me-2" alt="user">
                                                <span class="text-sm">John Doe</span>
                                            </div>
                                        </td>
                                        <td><span class="text-sm">Apr 15, 2025</span></td>
                                        <td><span class="text-sm">1.2 MB</span></td>
                                        <td class="text-center">
                                            <a href="#" class="text-info me-2" title="Download"><i
                                                    class="fa fa-download"></i></a>
                                            <a href="#" class="text-danger" title="Delete"><i class="fa fa-eye"></i></a>
                                        </td>
                                    </tr>
                                    <!-- Row 2 -->
                                    <tr>
                                        <td>
                                            <h6 class="mb-0 text-sm">Invoice_#12345.xlsx</h6>
                                        </td>
                                        <td><span class="text-xs font-weight-bold">Sales</span></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="../assets/img/team-2.jpg"
                                                    class="avatar avatar-xs rounded-circle me-2" alt="user">
                                                <span class="text-sm">Jane Smith</span>
                                            </div>
                                        </td>
                                        <td><span class="text-sm">Apr 14, 2025</span></td>
                                        <td><span class="text-sm">520 KB</span></td>
                                        <td class="text-center">
                                            <a href="#" class="text-info me-2" title="Download"><i
                                                    class="fa fa-download"></i></a>
                                            <a href="#" class="text-danger" title="Delete"><i class="fa fa-eye"></i></a>
                                        </td>
                                    </tr>
                                    <!-- Row 3 -->
                                    <tr>
                                        <td>
                                            <h6 class="mb-0 text-sm">Meeting_Notes.docx</h6>
                                        </td>
                                        <td><span class="text-xs font-weight-bold">HR</span></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="../assets/img/team-3.jpg"
                                                    class="avatar avatar-xs rounded-circle me-2" alt="user">
                                                <span class="text-sm">Alice Brown</span>
                                            </div>
                                        </td>
                                        <td><span class="text-sm">Apr 13, 2025</span></td>
                                        <td><span class="text-sm">300 KB</span></td>
                                        <td class="text-center">
                                            <a href="#" class="text-info me-2" title="Download"><i
                                                    class="fa fa-download"></i></a>
                                            <a href="#" class="text-danger" title="Delete"><i class="fa fa-eye"></i></a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="card h-100">
                    <div class="card-header pb-0">
                        <h6>Recent Activity</h6>
                        <p class="text-sm">
                            <i class="fa fa-clock text-primary" aria-hidden="true"></i>
                            <span class="font-weight-bold">Last 4 days</span>
                        </p>
                    </div>
                    <div class="card-body p-3">
                        <div class="timeline timeline-one-side">

                            <div class="timeline-block mb-3">
                                <span class="timeline-step">
                                    <i class="material-symbols-rounded text-success text-gradient">upload</i>
                                </span>
                                <div class="timeline-content">
                                    <h6 class="text-dark text-sm font-weight-bold mb-0">vivek uploaded: <i>Demo</i></h6>
                                    <p class="text-secondary font-weight-bold text-xs mt-1 mb-0">4 days ago</p>
                                </div>
                            </div>

                            <div class="timeline-block mb-3">
                                <span class="timeline-step">
                                    <i class="material-symbols-rounded text-danger text-gradient">delete</i>
                                </span>
                                <div class="timeline-content">
                                    <h6 class="text-dark text-sm font-weight-bold mb-0">Document deleted via folder:
                                        <i>PPT</i>
                                    </h6>
                                    <p class="text-secondary font-weight-bold text-xs mt-1 mb-0">4 days ago</p>
                                </div>
                            </div>

                            <div class="timeline-block mb-3">
                                <span class="timeline-step">
                                    <i class="material-symbols-rounded text-info text-gradient">update</i>
                                </span>
                                <div class="timeline-content">
                                    <h6 class="text-dark text-sm font-weight-bold mb-0">vivek uploaded new version:
                                        <i>v3</i>
                                    </h6>
                                    <p class="text-secondary font-weight-bold text-xs mt-1 mb-0">4 days ago</p>
                                </div>
                            </div>

                            <div class="timeline-block mb-3">
                                <span class="timeline-step">
                                    <i class="material-symbols-rounded text-info text-gradient">update</i>
                                </span>
                                <div class="timeline-content">
                                    <h6 class="text-dark text-sm font-weight-bold mb-0">vivek uploaded new version:
                                        <i>v2</i>
                                    </h6>
                                    <p class="text-secondary font-weight-bold text-xs mt-1 mb-0">4 days ago</p>
                                </div>
                            </div>

                            <div class="timeline-block">
                                <span class="timeline-step">
                                    <i class="material-symbols-rounded text-primary text-gradient">upload</i>
                                </span>
                                <div class="timeline-content">
                                    <h6 class="text-dark text-sm font-weight-bold mb-0">vivek uploaded: <i>Giving and
                                            Receiving Feedback</i></h6>
                                    <p class="text-secondary font-weight-bold text-xs mt-1 mb-0">4 days ago</p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>


            <?php
        include "components/footer.php";
      ?>
        </div>
    </main>
    <?php
?>

    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
    <script src="../assets/js/plugins/chartjs.min.js"></script>
    <script>
    var cty = document.getElementById("chart-bars").getContext("2d");

    new Chart(cty, {
        type: "bar",
        data: {
            labels: [".txt", ".ppt", ".png", ".zip", ".pdf", ".docx", ".xlsx"],
            datasets: [{
                label: "Document Count",
                tension: 0.4,
                borderWidth: 0,
                borderRadius: 4,
                borderSkipped: false,
                backgroundColor: "#43A047",
                data: [300, 15, 50, 10, 25, 35, 20], // <-- Replace with your actual data
                barThickness: 'flex'
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                }
            },
            interaction: {
                intersect: false,
                mode: 'index',
            },
            scales: {
                y: {
                    grid: {
                        drawBorder: false,
                        display: true,
                        drawOnChartArea: true,
                        drawTicks: false,
                        borderDash: [5, 5],
                        color: '#e5e5e5'
                    },
                    ticks: {
                        suggestedMin: 0,
                        suggestedMax: 60,
                        beginAtZero: true,
                        padding: 10,
                        font: {
                            size: 14,
                            lineHeight: 2
                        },
                        color: "#737373"
                    },
                },
                x: {
                    grid: {
                        drawBorder: false,
                        display: false,
                        drawOnChartArea: false,
                        drawTicks: false,
                        borderDash: [5, 5]
                    },
                    ticks: {
                        display: true,
                        color: '#737373',
                        padding: 10,
                        font: {
                            size: 14,
                            lineHeight: 2
                        },
                    }
                },
            },
        },
    });


    var ctx = document.getElementById("chart-storage").getContext("2d");

    const storageChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Used Storage', 'Free Storage'],
            datasets: [{
                data: [65, 35],
                backgroundColor: ['#90ee90', '#d3d3d3'],
                hoverBackgroundColor: ['#32cd32', '#a9a9a9'],
                borderWidth: 1
            }]
        },
        options: {
            cutout: '60%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: {
                            size: 14
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.label}: ${context.parsed}%`;
                        }
                    }
                }
            }
        }
    });


    var ctx2 = document.getElementById("chart-line").getContext("2d");

    new Chart(ctx2, {
        type: "line",
        data: {
            labels: ["J", "F", "M", "A", "M", "J", "J", "A", "S", "O", "N", "D"],
            datasets: [{
                label: "Documents Uploaded",
                tension: 0,
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: "#43A047",
                pointBorderColor: "transparent",
                borderColor: "#43A047",
                backgroundColor: "transparent",
                fill: true,
                data: [120, 230, 130, 440, 250, 360, 270, 180, 90, 300, 310, 220],
                maxBarThickness: 6

            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    callbacks: {
                        title: function(context) {
                            const fullMonths = ["January", "February", "March", "April", "May", "June",
                                "July", "August", "September", "October", "November", "December"
                            ];
                            return fullMonths[context[0].dataIndex];
                        }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index',
            },
            scales: {
                y: {
                    grid: {
                        drawBorder: false,
                        display: true,
                        drawOnChartArea: true,
                        drawTicks: false,
                        borderDash: [4, 4],
                        color: '#e5e5e5'
                    },
                    ticks: {
                        display: true,
                        color: '#737373',
                        padding: 10,
                        font: {
                            size: 12,
                            lineHeight: 2
                        },
                    }
                },
                x: {
                    grid: {
                        drawBorder: false,
                        display: false,
                        drawOnChartArea: false,
                        drawTicks: false,
                        borderDash: [5, 5]
                    },
                    ticks: {
                        display: true,
                        color: '#737373',
                        padding: 10,
                        font: {
                            size: 12,
                            lineHeight: 2
                        },
                    }
                },
            },
        },
    });
    </script>
    <script async defer src="https://buttons.github.io/buttons.js"></script>
    <script src="../assets/js/material-dashboard.min.js?v=3.2.0"></script>
</body>

</html>

<!-- Permission / Feature | Super Admin | Admin | Document Manager | Standard User | Reviewer / Approver | Viewer | Guest | Auditor
Manage system settings | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌
Create / Delete Organizations | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌
Manage Admins | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌
Manage Users (in org) | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌
Access All Documents (multi-org) | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌
Access Org-wide Documents | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅
Upload Documents | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌
Delete Documents | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌
Edit / Replace Documents | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌
Move / Organize Files / Folders | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌
Tag / Label Documents | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌
Download Documents | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅
Share Documents via Link / OTP | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ | ❌
Set Document Expiry / Retention | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌
Approve / Reject Documents | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌
Access Version History | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅
View Audit Logs | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅
View Access Logs / Activity | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅
Manage Tags / Classifications | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌
Manage Document Workflows | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌
API Access / Keys | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ -->