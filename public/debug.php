<?php
/**
 * TEST STACK - MANGANESE OS
 * Debug Alpine.js + PHP POST + Scope Parent
 */
$db = null;
try {
    require_once '../core/config.php';
    $db = get_db_connection();
} catch (Exception $e) {
    die("Erreur DB: " . $e->getMessage());
}

$debug_log = [];
$post_received = [];

// 1. Log ce qui arrive en POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_received = $_POST;
    $debug_log[] = "✓ POST reçu à " . date('H:i:s');
    $debug_log[] = "Action: " . ($_POST['action'] ?? 'N/A');
    $debug_log[] = "ID: " . ($_POST['id'] ?? 'N/A');
    $debug_log[] = "Type: " . ($_POST['type'] ?? 'N/A');
}

// 2. Test DB
try {
    $skills = $db->query("SELECT * FROM cv_skills LIMIT 3")->fetchAll();
    $debug_log[] = "✓ DB OK - " . count($skills) . " skills trouvés";
} catch (Exception $e) {
    $debug_log[] = "✗ DB ERROR: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>DEBUG - Manganese OS Stack Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-4xl font-black mb-8 text-slate-900">🔧 TEST STACK MANAGER</h1>

        <!-- LOGS -->
        <div class="bg-white p-6 rounded-xl shadow mb-8">
            <h2 class="text-xl font-bold mb-4 text-slate-700">📋 Event Log</h2>
            <div class="bg-slate-50 p-4 rounded font-mono text-xs space-y-1">
                <?php foreach ($debug_log as $log): ?>
                    <div><?= htmlspecialchars($log) ?></div>
                <?php endforeach; ?>
                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                    <div class="text-blue-600 font-bold">POST Data received</div>
                    <pre><?= htmlspecialchars(json_encode($_POST, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                <?php endif; ?>
            </div>
        </div>

        <!-- ALPINE.JS SCOPE TEST -->
        <div class="bg-white p-6 rounded-xl shadow mb-8" x-data="{ 
            tab: 'test1',
            editItem: null,
            testValue: 'initial',
            prepEdit(type, data = {}) {
                console.log('prepEdit called with:', type, data);
                this.editItem = { type: type, ...data };
                console.log('editItem is now:', this.editItem);
            }
        }">
            <h2 class="text-xl font-bold mb-4 text-slate-700">🧪 Alpine.js Scope Test</h2>

            <!-- Afficher l'état du scope -->
            <div class="bg-blue-50 p-4 rounded mb-4 font-mono text-xs">
                <div><strong>editItem state:</strong> <span x-text="JSON.stringify(editItem)"></span></div>
                <div><strong>tab:</strong> <span x-text="tab"></span></div>
            </div>

            <!-- Bouton pour tester prepEdit -->
            <div class="flex gap-4 mb-6">
                <button @click="prepEdit('test', {id: '123', name: 'Test Item'})" 
                        class="bg-blue-600 text-white px-4 py-2 rounded font-bold">
                    Test prepEdit()
                </button>
                <button @click="editItem = null" 
                        class="bg-gray-400 text-white px-4 py-2 rounded font-bold">
                    Reset editItem
                </button>
            </div>

            <!-- Modal Test -->
            <template x-if="editItem">
                <div class="fixed inset-0 bg-slate-900/70 z-50 flex items-center justify-center">
                    <div class="bg-white rounded-xl p-8 shadow-2xl">
                        <h3 class="text-2xl font-bold mb-4" x-text="'Modal: ' + editItem.type"></h3>
                        <div class="space-y-4">
                            <p><strong>ID:</strong> <span x-text="editItem.id"></span></p>
                            <p><strong>Name:</strong> <span x-text="editItem.name"></span></p>
                        </div>
                        <button @click="editItem = null" class="mt-6 bg-gray-500 text-white px-4 py-2 rounded">
                            Fermer
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- NESTED SCOPE TEST (comme admin_cv_editor) -->
        <div class="bg-white p-6 rounded-xl shadow mb-8" x-data="{ 
            tab: 'test1',
            editItem: null,
            prepEdit(type, data = {}) {
                console.log('prepEdit called (parent):', type, data);
                this.editItem = { type: type, ...data };
            }
        }">
            <h2 class="text-xl font-bold mb-4 text-slate-700">🔀 Nested Scope Test (Parent)</h2>
            
            <!-- Parent State -->
            <div class="bg-green-50 p-4 rounded mb-4 font-mono text-xs">
                <div><strong>Parent editItem:</strong> <span x-text="JSON.stringify(editItem)"></span></div>
            </div>

            <!-- Nested DIV (comme admin_cv_editor.php) -->
            <div x-data="{ section: 'profile' }" class="bg-yellow-50 p-4 rounded mb-4 border-2 border-yellow-200">
                <h3 class="font-bold mb-3">Nested X-Data (Child Scope)</h3>
                
                <div class="bg-yellow-100 p-3 rounded mb-3 font-mono text-xs">
                    <div><strong>Child section:</strong> <span x-text="section"></span></div>
                    <div><strong>Can access parent editItem?</strong> <span x-text="editItem ? 'YES ✓' : 'NO ✗'"></span></div>
                    <div><strong>Can call parent prepEdit?</strong> <button @click="try { prepEdit('exp', {id: 'test'}); } catch(e) { alert('ERROR: ' + e); }" class="bg-red-500 text-white px-2 py-1 rounded text-xs">Test</button></div>
                </div>

                <!-- Try prepEdit from nested scope -->
                <button @click="prepEdit('nested', {id: '456', nested: true})" 
                        class="bg-purple-600 text-white px-4 py-2 rounded font-bold">
                    Call prepEdit from Nested Scope
                </button>
            </div>

            <!-- Modal at parent level -->
            <template x-if="editItem">
                <div class="fixed inset-0 bg-slate-900/70 z-50 flex items-center justify-center">
                    <div class="bg-white rounded-xl p-8 shadow-2xl">
                        <h3 class="text-2xl font-bold mb-4" x-text="'Nested Test Modal: ' + editItem.type"></h3>
                        <p><strong>From:</strong> <span x-text="editItem.nested ? 'NESTED SCOPE' : 'PARENT SCOPE'"></span></p>
                        <button @click="editItem = null" class="mt-6 bg-gray-500 text-white px-4 py-2 rounded">
                            Fermer
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- REAL FORM TEST -->
        <div class="bg-white p-6 rounded-xl shadow" x-data="{ 
            tab: 'apps',
            editItem: null,
            prepEdit(type, data = {}) {
                this.editItem = { type: type, ...data };
                console.log('prepEdit from REAL test:', type, this.editItem);
            }
        }">
            <h2 class="text-xl font-bold mb-4 text-slate-700">📝 Form POST Test</h2>
            
            <div class="bg-slate-50 p-4 rounded mb-4 font-mono text-xs">
                <div><strong>editItem:</strong> <span x-text="JSON.stringify(editItem)"></span></div>
            </div>

            <!-- Test avec vraies données DB -->
            <div x-data="{ section: 'skills' }" class="mb-6">
                <h3 class="font-bold mb-3">Skills from DB:</h3>
                <div class="grid gap-2">
                    <?php foreach ($skills as $s): ?>
                    <div class="bg-gray-100 p-3 rounded flex justify-between items-center">
                        <span><?= htmlspecialchars($s['label']) ?></span>
                        <button @click="prepEdit('skill', <?= htmlspecialchars(json_encode($s), ENT_QUOTES, 'UTF-8') ?>)" 
                                class="bg-blue-500 text-white px-3 py-1 rounded text-sm">
                            Edit
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Modal avec FORM -->
            <template x-if="editItem">
                <div class="fixed inset-0 bg-slate-900/70 z-50 flex items-center justify-center p-4">
                    <div class="bg-white rounded-xl p-8 shadow-2xl w-full max-w-md">
                        <h3 class="text-2xl font-bold mb-6">Edit <span x-text="editItem.type"></span></h3>
                        
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" :value="'test_' + editItem.type">
                            <input type="hidden" name="type" :value="editItem.type">
                            <input type="hidden" name="id" :value="editItem.id">

                            <template x-if="editItem.type === 'skill'">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-bold mb-1">Label</label>
                                        <input type="text" name="label" x-model="editItem.label" class="w-full border p-2 rounded">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold mb-1">Level</label>
                                        <input type="text" name="level_text" x-model="editItem.level_text" class="w-full border p-2 rounded">
                                    </div>
                                </div>
                            </template>

                            <div class="flex gap-3 mt-6">
                                <button type="button" @click="editItem = null" class="flex-1 bg-gray-400 text-white py-2 rounded font-bold">
                                    Cancel
                                </button>
                                <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded font-bold">
                                    Save & POST
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <script>
        // Log tous les events Alpine
        document.addEventListener('alpine:init', () => {
            console.log('Alpine.js initialized');
        });
    </script>
</body>
</html>
