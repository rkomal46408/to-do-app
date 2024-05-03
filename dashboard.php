
<?php
session_start();
include 'db.php';  // Include the database connection file

if (!isset($_SESSION['userID'])) {
    header('Location: index.php'); // Redirect to login page if not logged in
    exit();
}

$userID = $_SESSION['userID'];
$query = $pdo->prepare("SELECT username FROM Users WHERE userID = ?");
$query->execute([$userID]);
$user = $query->fetch();

// Handle task addition and update
if (isset($_POST['addTask']) || isset($_POST['updateTask'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $dueDate = $_POST['dueDate'];
    $priority = $_POST['priority'];
    $category = $_POST['category'];
    if (isset($_POST['updateTask'])) {
        $taskID = $_POST['taskID'];
        $updateTask = $pdo->prepare("UPDATE Tasks SET title = ?, description = ?, dueDate = ?, priority = ?, category = ? WHERE taskID = ?");
        $updateTask->execute([$title, $description, $dueDate, $priority, $category, $taskID]);
    } else {
        $insertTask = $pdo->prepare("INSERT INTO Tasks (userID, title, description, dueDate, priority, category, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $insertTask->execute([$userID, $title, $description, $dueDate, $priority, $category]);
    }
    header('Location: dashboard.php'); // Refresh the page
}

// Handle task completion toggle
if (isset($_POST['toggleComplete'])) {
    $taskID = $_POST['taskID'];
    $taskStatus = $_POST['taskStatus'] == 'pending' ? 'completed' : 'pending';
    $pdo->prepare("UPDATE Tasks SET status = ? WHERE taskID = ?")->execute([$taskStatus, $taskID]);
    header('Location: dashboard.php'); // Refresh the page to update visual
}

// Handle task deletion
if (isset($_POST['deleteTask'])) {
    $taskID = $_POST['taskID'];
    $pdo->prepare("DELETE FROM Tasks WHERE taskID = ?")->execute([$taskID]);
    header('Location: dashboard.php'); // Refresh the page to reflect changes
}

// Fetch all tasks for the user
$tasksQuery = $pdo->prepare("SELECT * FROM Tasks WHERE userID = ?");
$tasksQuery->execute([$userID]);
$tasks = $tasksQuery->fetchAll();

// Calculate task statistics for visuals
$totalTasks = count($tasks);
$completedTasks = count(array_filter($tasks, function ($t) { return $t['status'] === 'completed'; }));
$pendingTasks = $totalTasks - $completedTasks;
$completionScore = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Productivity App</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>


    </style>
    
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="#">Productivity App</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <form method="post">
                    <button name="logout" class="btn btn-link text-white" style="text-decoration: none;">Logout <i class="fas fa-sign-out-alt"></i></button>
                </form>
            </li>
        </ul>
    </div>
</nav>

<div class="container">
    <div class="row" style="margin-top:25px">
        

        <div class="col-md-4 text-center" style="margin-top:8%">
            <h2>Task Manager</h2>
            <p>Stay organized and productive.</p>
            <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#addTaskModal"><i class="fas fa-plus-circle"></i> Add New Task</button>
        </div>

        
        <div class="col-md-4">
            <canvas id="pointChart"></canvas>
        </div>
        
        <div class="col-md-4">
            <canvas id="completionChart"></canvas>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Complete</th>
                                <th scope="col">Title</th>
                                <th scope="col">Details</th>
                                <th scope="col">Priority</th>
                                <th scope="col">Category</th>
                                <th scope="col">Due Date</th>
                                <th scope="col">Edit</th>
                                <th scope="col">Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task): ?>
                            <tr class="<?php echo $task['status'] == 'completed' ? 'table-success' : ''; ?>">
                                <td>
                                    <form method="post">
                                        <input type="hidden" name="taskID" value="<?php echo $task['taskID']; ?>">
                                        <input type of "hidden" name="taskStatus" style="display:none" value="<?php echo $task['status']; ?>">
                                        <button type="submit" name="toggleComplete" class="btn btn-outline-success btn-sm"><?php echo $task['status'] == 'completed' ? '<i class="fas fa-redo"></i>' : '<i class="fas fa-check"></i>'; ?></button>
                                    </form>
                                </td>
                                <td><?php echo htmlspecialchars($task['title']); ?></td>
                                <td><?php echo htmlspecialchars($task['description']); ?></td>
                                <td><?php echo htmlspecialchars($task['priority']); ?></td>
                                <td><?php echo htmlspecialchars($task['category']); ?></td>
                                <td><?php echo htmlspecialchars($task['dueDate']); ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#addTaskModal" onclick="editTask(<?php echo $task['taskID'] . ', \'' . addslashes($task['title']) . '\', \'' . addslashes($task['description']) . '\', \'' . $task['dueDate'] . '\', \'' . $task['priority'] . '\', \'' . $task['category'] . '\''; ?>)"><i class="fas fa-edit"></i></button>
                                </td>
                                <td>
                                    <form method="post">
                                        <input type="hidden" name="taskID" value="<?php echo $task['taskID']; ?>">
                                        <button type="submit" name="deleteTask" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Task Modal -->
<div class="modal fade" id="addTaskModal" tabindex="-1" role="dialog" aria-labelledby="addTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTaskModalLabel">Add/Edit Task</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" id="taskID" name="taskID">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="dueDate">Due Date</label>
                        <input type="date" class="form-control" id="dueDate" name="dueDate" required>
                    </div>
                    <div class="form-group">
                        <label for="priority">Priority</label>
                        <select class="form-control" id="priority" name="priority">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="category">Category</label>
                        <input type="text" class="form-control" id="category" name="category">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="addTask">Save Task</button>
                        <button type="submit" class="btn btn-info" name="updateTask">Update Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

 <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <span>Productivity App</span>
        </div>
    </footer>
    
<script>
function editTask(taskID, title, description, dueDate, priority, category) {
    document.getElementById('taskID').value = taskID;
    document.getElementById('title').value = title;
    document.getElementById('description').value = description;
    document.getElementById('dueDate').value = dueDate;
    document.getElementById('priority').value = priority;
    document.getElementById('category').value = category;
    document.getElementById('addTaskModalLabel').innerHTML = 'Edit Task';
}
</script>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                    <script>
                        var ctx = document.getElementById('completionChart').getContext('2d');
                        var completionChart = new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: ['Completed', 'Pending'],
                                datasets: [{
                                    label: 'Task Status',
                                    data: [<?php echo $completedTasks; ?>, <?php echo $pendingTasks; ?>],
                                    backgroundColor: ['rgb(75, 192, 192)', 'rgb(255, 99, 132)'],
                                    borderColor: ['rgb(75, 192, 192)', 'rgb(255, 99, 132)'],
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                legend: {
                                    position: 'bottom',
                                },
                                animation: {
                                    animateScale: true,
                                    animateRotate: true
                                }
                            }
                        });
                    </script>
                    <canvas id="pointChart"></canvas>
                    <script>
                        var ptx = document.getElementById('pointChart').getContext('2d');
                        var pointChart = new Chart(ptx, {
                            type: 'doughnut',
                            data: {
                                labels: ['Completion Score'],
                                datasets: [{
                                    label: 'Completion Score',
                                    data: [<?php echo $completionScore; ?>, 100 - <?php echo $completionScore; ?>],
                                    backgroundColor: ['rgb(54, 162, 235)', 'rgba(211, 211, 211)'],
                                    borderColor: ['rgb(54, 162, 235)'],
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                circumference: 180,
                                rotation: -90,
                                responsive: true
                            }
                        });
                    </script>
</body>
</html>

