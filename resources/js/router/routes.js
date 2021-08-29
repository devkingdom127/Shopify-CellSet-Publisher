export const routes = [
    {
        path: "/",
        name: "main",
        component: () => import("../views/main/Main")
    },
    { path: "*", redirect: { name: "error" } }
]