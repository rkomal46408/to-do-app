<?php
session_start();
include 'db.php';

$response = ['success' => false, 'message' => 'An error occurred'];

if (!isset($_SESSION['userID'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$userID = $_SESSION['userID'];

if (isset($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {
        case 'addTask':
            if (isset($_POST['title'], $_POST['description'], $_POST['dueDate'], $_POST['priority'], $_POST['category'])) {
                $title = $_POST['title'];
                $description = $_POST['description'];
                $dueDate = $_POST['dueDate'];
                $priority = $_POST['priority'];
                $category = $_POST['category'];

                $insertTask = $pdo->prepare("INSERT INTO Tasks (userID, title, description, dueDate, priority, category, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                if ($insertTask->execute([$userID, $title, $description, $dueDate, $priority, $category])) {
                    $taskID = $pdo->lastInsertId();
                    $response = [
                        'success' => true,
                        'message' => 'Task added successfully',
                        'task' => [
                            'taskID' => $taskID,
                            'title' => htmlspecialchars($title),
                            'description' => htmlspecialchars($description),
                            'dueDate' => $dueDate,
                            'priority' => htmlspecialchars($priority),
                            'category' => htmlspecialchars($category),
                            'status' => 'pending'
                        ]
                    ];
                } else {
                    $response['message'] = 'Failed to add task';
                }
            } else {
                $response['message'] = 'Invalid input';
            }
            break;
        
        case 'updateTask':
            if (isset($_POST['taskID'], $_POST['title'], $_POST['description'], $_POST['dueDate'], $_POST['priority'], $_POST['category'])) {
                $taskID = $_POST['taskID'];
                $title = $_POST['title'];
                $description = $_POST['description'];
                $dueDate = $_POST['dueDate'];
                $priority = $_POST['priority'];
                $category = $_POST['category'];

                $updateTask = $pdo->prepare("UPDATE Tasks SET title = ?, description = ?, dueDate = ?, priority = ?, category = ? WHERE taskID = ?");
                if ($updateTask->execute([$title, $description, $dueDate, $priority, $category, $taskID])) {
                    $response = [
                        'success' => true,
                        'message' => 'Task updated successfully',
                        'task' => [
                            'taskID' => $taskID,
                            'title' => htmlspecialchars($title),
                            'description' => htmlspecialchars($description),
                            'dueDate' => $dueDate,
                            'priority' => htmlspecialchars($priority),
                            'category' => htmlspecialchars($category),
                            'status' => 'pending'
                        ]
                    ];
                } else {
                    $response['message'] = 'Failed to update task';
                }
            } else {
                $response['message'] = 'Invalid input';
            }
            break;

        case 'deleteTask':
            if (isset($_POST['taskID'])) {
                $taskID = $_POST['taskID'];

                $deleteTask = $pdo->prepare("DELETE FROM Tasks WHERE taskID = ?");
                if ($deleteTask->execute([$taskID])) {
                    $response = [
                        'success' => true,
                        'message' => 'Task deleted successfully'
                    ];
                } else {
                    $response['message'] = 'Failed to delete task';
                }
            } else {
                $response['message'] = 'Invalid input';
            }
            break;

        case 'toggleComplete':
            if (isset($_POST['taskID'], $_POST['taskStatus'])) {
                $taskID = $_POST['taskID'];
                $taskStatus = $_POST['taskStatus'] == 'pending' ? 'completed' : 'pending';

                $toggleTask = $pdo->prepare("UPDATE Tasks SET status = ? WHERE taskID = ?");
                if ($toggleTask->execute([$taskStatus, $taskID])) {
                    $taskQuery = $pdo->prepare("SELECT * FROM Tasks WHERE taskID = ?");
                    $taskQuery->execute([$taskID]);
                    $task = $taskQuery->fetch();

                    $response = [
                        'success' => true,
                        'message' => 'Task status updated successfully',
                        'task' => [
                            'taskID' => $task['taskID'],
                            'title' => htmlspecialchars($task['title']),
                            'description' => htmlspecialchars($task['description']),
                            'dueDate' => $task['dueDate'],
                            'priority' => htmlspecialchars($task['priority']),
                            'category' => htmlspecialchars($task['category']),
                            'status' => $task['status']
                        ]
                    ];
                } else {
                    $response['message'] = 'Failed to update task status';
                }
            } else {
                $response['message'] = 'Invalid input';
            }
            break;

        default:
            $response['message'] = 'Invalid action';
            break;
    }
}

echo json_encode($response);
?>
