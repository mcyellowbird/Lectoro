function generateSidebar(data) {
    return `
        <div id="sidebar-content" class="sidebar z-50" tabindex="-1">
        <div class="flex items-center flex-col p-4 bg-background h-screen w-280">
            <a href="#" class="flex items-center w-full text-3xl pl-1">
                <i class="pr-4">
                    <object class="h-10 w-10" data="${data.logoSVG}" type="image/svg+xml">
                        <img src="${data.logoSVG}">
                    </object>
                </i>
                <span>LECTORO</span>
            </a>

            <hr class="my-4 opacity-25 w-full">

            <ul class="text-md flex flex-col flex-grow w-240">
                <li class="my-1">
                    <button href="#" class="componentButton componentButtonActive ">
                        <i class="bx bxs-home"></i>
                        <span>Dashboard</span>
                    </button>
                </li>
                <li class="my-1">
                    <button href="#" class="componentButton">
                        <i class="bx bxs-message"></i>
                        <span>Messages</span>
                    </button>
                </li>
                <li class="my-1">
                    <button href="#" class="componentButton">
                        <i class="bx bxs-book-alt"></i>
                        <span>Subjects</span>
                    </button>
                </li>
                <li class="my-1">
                    <button href="#" class="componentButton">
                        <i class="bx bxs-user"></i>
                        <span>Students</span>
                    </button>
                </li>
                <li class="my-1">
                    <button href="#" class="componentButton">
                        <i class="bx bxs-bar-chart-alt-2"></i>
                        <span>Reports</span>
                    </button>
                </li>
            </ul>

            <hr class="my-4 opacity-25 w-full">

            <div class="flex text-left w-full">
                <div class="flex-grow">
                    <button data-dropdown-toggle="dropdown-menu" type="button" class="group w-full flex items-center" id="dropdown-button">
                        <!-- Will have to change to an img -->
                        <i class="bx bxs-user text-2xl pr-1.5"></i>
                        <span id="username" class="align-top">${data.user.username}</span>
                        <i id="dropdown-arrow" class="group-hover:text-accent transition-color ease-out duration-300 bx bx-chevron-down pl-1.5"></i>
                    </button>
                </div>
                <div class="self-center">
                    <button class="hover:text-accent shadow-lg" id="sidebar-large-button" data-drawer-target="sidebar-content" data-drawer-body-scrolling="true" data-drawer-hide="sidebar-content" data-drawer-backdrop="false" data-drawer-placement="left"><i class="bx bx-left-arrow"></i></button>
                </div>

                <div id="dropdown-menu"
                    class="hidden overflow-hidden z-20 mt-2 w-40 rounded-lg bg-menu shadow-[0_.25rem_.5rem_rgba(0,0,0,0.31)]">
                    <div>
                        <div class="py-3 select-none">
                            <p class="px-4 ">${data.user.firstName} ${data.user.lastName}</p>
                            <p class="px-4 text-sm text-textAccent">${data.user.email}</p>
                        </div>
                        <hr class="opacity-20">
                        <a href="#"
                            class="hover:bg-buttonHover transition ease-out duration-300 block px-4 py-2 text-md">Settings</a>
                        <a href="#"
                            class="hover:bg-buttonHover transition ease-out duration-300 block px-4 py-2 text-md">Profile</a>
                        <hr class="opacity-20">
                        <a href="#"
                            class="hover:bg-buttonHover transition ease-out duration-300 block px-4 py-2 text-md">Sign
                            out</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="shadow-divider bg-blackOpaque w-6 h-screen z-10"></div>
    </div>

    <div id="sidebar-content-small" class="sidebar z-40" tabindex="-2">
        <div class="flex items-center flex-col p-4 bg-background h-screen w-20">
            <a href="#" class="text-3xl">
                <i>
                    <object class="h-10 w-10" data="${data.logoSVG}" type="image/svg+xml">
                        <img src="${data.logoSVG}">
                    </object>
                </i>
            </a>

            <hr class="my-4 opacity-25 w-full">

            <ul class="text-2xl flex flex-col items-center flex-grow w-20">
                <li class="my-1">
                    <button href="#" class="componentButtonSmall componentButtonSmallActive">
                        <i class="bx bxs-home"></i>
                    </button>
                </li>
                <li class="my-1">
                    <button href="#" class="componentButtonSmall">
                        <i class="bx bxs-message"></i>
                    </button>
                </li>
                <li class="my-1">
                    <button href="#" class="componentButtonSmall">
                        <i class="bx bxs-book-alt"></i>
                    </button>
                </li>
                <li class="my-1">
                    <button href="#" class="componentButtonSmall">
                        <i class="bx bxs-user"></i>
                    </button>
                </li>
                <li class="my-1">
                    <button href="#" class="componentButtonSmall">
                        <i class="bx bxs-bar-chart-alt-2"></i>
                    </button>
                </li>
            </ul>

            <hr class="my-4 opacity-25 w-full">

            <div class="flex items-center">
                <div>
                    <button data-dropdown-toggle="dropdown-menu-small" data-dropdown-placement="top-end" type="button" class="group w-full flex items-center"
                        id="dropdown-button-small">
                        <!-- Will have to change to an img -->
                        <i class="group-hover:text-accent transition-color ease-out duration-300 bx bxs-user text-2xl pr-1.5"></i>
                    </button>
                </div>
                <div class="self-center relative">
                    <button id="sidebar-small-button" class="hover:text-accent shadow-lg" data-drawer-target="sidebar-content-small" data-drawer-body-scrolling="true" data-drawer-show="sidebar-content" data-drawer-backdrop="false" data-drawer-placement="left"><i class="absolute left-[72px] right-0 top-0 bottom-0 bx bx-right-arrow"></i></button>
                </div>

                <div id="dropdown-menu-small"
                    class="hidden overflow-hidden z-20 mt-2 w-40 rounded-lg bg-menu shadow-[0_.25rem_.5rem_rgba(0,0,0,0.31)]">
                    <div>
                        <div class="py-3 select-none">
                            <p class="px-4 ">${data.user.firstName} ${data.user.lastName}</p>
                            <p class="px-4 text-sm text-textAccent">${data.user.email}</p>
                        </div>
                        <hr class="opacity-20">
                        <a href="#"
                            class="hover:bg-buttonHover transition ease-out duration-300 block px-4 py-2 text-md">Settings</a>
                        <a href="#"
                            class="hover:bg-buttonHover transition ease-out duration-300 block px-4 py-2 text-md">Profile</a>
                        <hr class="opacity-20">
                        <a href="#"
                            class="hover:bg-buttonHover transition ease-out duration-300 block px-4 py-2 text-md">Sign
                            out</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="shadow-divider bg-blackOpaque w-6 h-screen z-10"></div>
    </div>
    `
}
