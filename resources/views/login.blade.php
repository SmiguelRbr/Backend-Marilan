<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><title>Login - Marilan</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 border border-gray-300 w-full max-w-sm rounded-sm shadow-lg">
        <div class="flex justify-center mb-6">
            <img src="https://upload.wikimedia.org/wikipedia/commons/f/f2/Grupo_marilan.png" class="h-12">
        </div>
        <h2 class="text-center text-xs font-bold uppercase tracking-widest text-gray-500 mb-6">Acesso ao Almoxarifado</h2>
        
        <form action="{{ route('login.post') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-[10px] font-bold uppercase text-gray-600 mb-1">Crachá</label>
                <input type="text" name="cracha" class="w-full border border-gray-300 px-3 py-2 text-sm rounded-sm focus:border-orange-500 outline-none">
            </div>
            <div class="mb-6">
                <label class="block text-[10px] font-bold uppercase text-gray-600 mb-1">Senha</label>
                <input type="password" name="password" class="w-full border border-gray-300 px-3 py-2 text-sm rounded-sm focus:border-orange-500 outline-none">
            </div>
            @if($errors->any())
                <p class="text-red-600 text-[10px] font-bold mb-4 uppercase">{{ $errors->first() }}</p>
            @endif
            <button type="submit" class="w-full bg-[#F26419] text-white py-2 text-xs font-bold uppercase tracking-widest hover:bg-[#D95311] transition-colors">Entrar no Sistema</button>
        </form>
    </div>
</body>
</html>