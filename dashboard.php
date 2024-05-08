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
            <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#addTaskModal" onclick="clearForm()"><i class="fas fa-plus-circle"></i> Add New Task</button>
            <button class="btn btn-secondary mb-3" onclick="syncTasks()"><i class="fas fa-sync"></i> Sync</button>
            <div id="message" class="alert" style="display:none;"></div>
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
                        <tbody id="taskTableBody">
                            <?php foreach ($tasks as $task): ?>
                            <tr class="<?php echo $task['status'] == 'completed' ? 'table-success' : ''; ?>" id="taskRow-<?php echo $task['taskID']; ?>">
                                <td>
                                    <button type="button" class="btn btn-outline-success btn-sm" onclick="toggleComplete(<?php echo $task['taskID']; ?>, '<?php echo $task['status']; ?>')">
                                        <?php echo $task['status'] == 'completed' ? '<i class="fas fa-redo"></i>' : '<i class="fas fa-check"></i>'; ?>
                                    </button>
                                </td>
                                <td><?php echo htmlspecialchars($task['title']); ?></td>
                                <td><?php echo htmlspecialchars($task['description']); ?></td>
                                <td><?php echo htmlspecialchars($task['priority']); ?></td>
                                <td><?php echo htmlspecialchars($task['category']); ?></td>
                                <td><?php echo htmlspecialchars($task['dueDate']); ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#addTaskModal" onclick="editTask(<?php echo $task['taskID'] . ', \'' . addslashes($task['title']) . '\', \'' . addslashes($task['description']) . '\', \'' . $task['dueDate'] . '\', \'' . $task['priority'] . '\', \'' . $task['category'] . '\''; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteTask(<?php echo $task['taskID']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
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
                <form id="taskForm">
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
                        <button type="button" class="btn btn-primary" onclick="saveTask()" id="saveTaskButton">Save Task</button>
                        <button type="button" class="btn btn-info" onclick="updateTask()" id="updateTaskButton">Update Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>



<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
function clearForm() {
    $('#taskForm')[0].reset();
    $('#taskID').val('');
    $('#addTaskModalLabel').text('Add Task');
    $('#saveTaskButton').show();
    $('#updateTaskButton').hide();
}

function editTask(taskID, title, description, dueDate, priority, category) {
    document.getElementById('taskID').value = taskID;
    document.getElementById('title').value = title;
    document.getElementById('description').value = description;
    document.getElementById('dueDate').value = dueDate;
    document.getElementById('priority').value = priority;
    document.getElementById('category').value = category;
    document.getElementById('addTaskModalLabel').innerHTML = 'Edit Task';
    $('#saveTaskButton').hide();
    $('#updateTaskButton').show();
}

function saveTask() {
    var formData = $('#taskForm').serialize();
    var task = {
        title: $('#title').val(),
        description: $('#description').val(),
        dueDate: $('#dueDate').val(),
        priority: $('#priority').val(),
        category: $('#category').val(),
        action: 'addTask'
    };
    if (navigator.onLine) {
        $.ajax({
            type: 'POST',
            url: 'task_actions.php',
            data: formData + '&action=addTask',
            dataType: 'json',
            success: function(response) {
                $('#addTaskModal').modal('hide');
                showMessage(response.message, response.success ? 'alert-success' : 'alert-danger');
                if (response.success) {
                    appendTaskRow(response.task);
                    updateCharts();
                }
            },
            error: function(xhr, status, error) {
                console.log(xhr.responseText);
            }
        });
    } else {
        var tasks = JSON.parse(localStorage.getItem('tasks')) || [];
        task.taskID = Date.now();
        task.status = 'pending';
        tasks.push(task);
        localStorage.setItem('tasks', JSON.stringify(tasks));
        $('#addTaskModal').modal('hide');
        showMessage('Task saved locally', 'alert-warning');
        appendTaskRow(task);
        updateCharts();
    }
}

function updateTask() {
    var formData = $('#taskForm').serialize();
    var task = {
        taskID: $('#taskID').val(),
        title: $('#title').val(),
        description: $('#description').val(),
        dueDate: $('#dueDate').val(),
        priority: $('#priority').val(),
        category: $('#category').val(),
        action: 'updateTask'
    };
    if (navigator.onLine) {
        $.ajax({
            type: 'POST',
            url: 'task_actions.php',
            data: formData + '&action=updateTask',
            dataType: 'json',
            success: function(response) {
                $('#addTaskModal').modal('hide');
                showMessage(response.message, response.success ? 'alert-success' : 'alert-danger');
                if (response.success) {
                    updateTaskRow(response.task);
                    updateCharts();
                }
            },
            error: function(xhr, status, error) {
                console.log(xhr.responseText);
            }
        });
    } else {
        var tasks = JSON.parse(localStorage.getItem('tasks')) || [];
        var taskIndex = tasks.findIndex(t => t.taskID == task.taskID);
        if (taskIndex > -1) {
            tasks[taskIndex] = task;
            localStorage.setItem('tasks', JSON.stringify(tasks));
            $('#addTaskModal').modal('hide');
            showMessage('Task updated locally', 'alert-warning');
            updateTaskRow(task);
            updateCharts();
        }
    }
}

function deleteTask(taskID) {
    if (!confirm("Are you sure you want to delete this task?")) {
        return;
    }
    if (navigator.onLine) {
        $.ajax({
            type: 'POST',
            url: 'task_actions.php',
            data: { taskID: taskID, action: 'deleteTask' },
            dataType: 'json',
            success: function(response) {
                showMessage(response.message, response.success ? 'alert-success' : 'alert-danger');
                if (response.success) {
                    removeTaskRow(taskID);
                    updateCharts();
                }
            },
            error: function(xhr, status, error) {
                console.log(xhr.responseText);
            }
        });
    } else {
        var tasks = JSON.parse(localStorage.getItem('tasks')) || [];
        tasks = tasks.filter(t => t.taskID != taskID);
        localStorage.setItem('tasks', JSON.stringify(tasks));
        showMessage('Task deleted locally', 'alert-warning');
        removeTaskRow(taskID);
        updateCharts();
    }
}

function toggleComplete(taskID, currentStatus) {
    if (navigator.onLine) {
        $.ajax({
            type: 'POST',
            url: 'task_actions.php',
            data: { taskID: taskID, taskStatus: currentStatus, action: 'toggleComplete' },
            dataType: 'json',
            success: function(response) {
                showMessage(response.message, response.success ? 'alert-success' : 'alert-danger');
                if (response.success) {
                    updateTaskRow(response.task);
                    updateCharts();
                }
            },
            error: function(xhr, status, error) {
                console.log(xhr.responseText);
            }
        });
    } else {
        var tasks = JSON.parse(localStorage.getItem('tasks')) || [];
        var taskIndex = tasks.findIndex(t => t.taskID == taskID);
        if (taskIndex > -1) {
            tasks[taskIndex].status = currentStatus == 'pending' ? 'completed' : 'pending';
            localStorage.setItem('tasks', JSON.stringify(tasks));
            showMessage('Task status updated locally', 'alert-warning');
            updateTaskRow(tasks[taskIndex]);
            updateCharts();
        }
    }
}

function syncTasks() {
    var tasks = JSON.parse(localStorage.getItem('tasks')) || [];
    if (tasks.length === 0) {
        showMessage('No tasks to sync', 'alert-info');
        return;
    }
    tasks.forEach(function(task) {
        $.ajax({
            type: 'POST',
            url: 'task_actions.php',
            data: task,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    removeTaskRow(task.taskID);
                }
            },
            error: function(xhr, status, error) {
                console.log(xhr.responseText);
            }
        });
    });
    localStorage.removeItem('tasks');
    showMessage('All tasks synced', 'alert-success');
    updateCharts();
}

function showMessage(message, alertClass) {
    var messageDiv = $('#message');
    messageDiv.removeClass('alert-success alert-danger alert-warning alert-info').addClass(alertClass).html(message).show();
    setTimeout(function() {
        messageDiv.hide();
    }, 3000);
}

function appendTaskRow(task) {
    var newRow = `<tr class="${task.status === 'completed' ? 'table-success' : ''}" id="taskRow-${task.taskID}">
        <td>
            <button type="button" class="btn btn-outline-success btn-sm" onclick="toggleComplete(${task.taskID}, '${task.status}')">
                ${task.status === 'completed' ? '<i class="fas fa-redo"></i>' : '<i class="fas fa-check"></i>'}
            </button>
        </td>
        <td>${task.title}</td>
        <td>${task.description}</td>
        <td>${task.priority}</td>
        <td>${task.category}</td>
        <td>${task.dueDate}</td>
        <td>
            <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#addTaskModal" onclick="editTask(${task.taskID}, '${task.title}', '${task.description}', '${task.dueDate}', '${task.priority}', '${task.category}')">
                <i class="fas fa-edit"></i>
            </button>
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm" onclick="deleteTask(${task.taskID})">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>`;
    $('#taskTableBody').append(newRow);
}

function updateTaskRow(task) {
    // Find the row by taskID
    var row = $('#taskRow-' + task.taskID);
    
    // Update each cell individually to avoid changing the row structure
    row.find('td:eq(0)').html(`
        <button type="button" class="btn btn-outline-success btn-sm" onclick="toggleComplete(${task.taskID}, '${task.status}')">
            ${task.status === 'completed' ? '<i class="fas fa-redo"></i>' : '<i class="fas fa-check"></i>'}
        </button>
    `);
    
    row.find('td:eq(1)').text(task.title);
    row.find('td:eq(2)').text(task.description);
    row.find('td:eq(3)').text(task.priority);
    row.find('td:eq(4)').text(task.category);
    row.find('td:eq(5)').text(task.dueDate);
    row.find('td:eq(6)').html(`
        <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#addTaskModal" onclick="editTask(${task.taskID}, '${task.title}', '${task.description}', '${task.dueDate}', '${task.priority}', '${task.category}')">
            <i class="fas fa-edit"></i>
        </button>
    `);
    row.find('td:eq(7)').html(`
        <button type="button" class="btn btn-danger btn-sm" onclick="deleteTask(${task.taskID})">
            <i class="fas fa-trash"></i>
        </button>
    `);
    
    // Update the row class based on task status
    if (task.status === 'completed') {
        row.addClass('table-success');
    } else {
        row.removeClass('table-success');
    }
}


function removeTaskRow(taskID) {
    $('#taskRow-' + taskID).remove();
}

function updateCharts() {
    var completedTasks = $('#taskTableBody tr.table-success').length;
    var totalTasks = $('#taskTableBody tr').length;
    var pendingTasks = totalTasks - completedTasks;
    var completionScore = totalTasks > 0 ? (completedTasks / totalTasks) * 100 : 0;

    completionChart.data.datasets[0].data = [completedTasks, pendingTasks];
    completionChart.update();

    pointChart.data.datasets[0].data = [completionScore, 100 - completionScore];
    pointChart.update();
}

$(document).ready(function() {
    if (localStorage.getItem('tasks')) {
        var tasks = JSON.parse(localStorage.getItem('tasks'));
        tasks.forEach(function(task) {
            appendTaskRow(task);
        });
    }
});

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
