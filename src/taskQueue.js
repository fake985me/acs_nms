const queues = new Map(); // key: deviceId, value: array of tasks

export function addTask(deviceId, task) {
  if (!queues.has(deviceId)) {
    queues.set(deviceId, []);
  }
  queues.get(deviceId).push(task);
}

export function getNextTask(deviceId) {
  const q = queues.get(deviceId) || [];
  if (!q.length) return null;
  return q.shift();
}

export function listTasks(deviceId) {
  return queues.get(deviceId) || [];
}
