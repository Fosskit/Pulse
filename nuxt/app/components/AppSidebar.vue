<script setup lang="ts">
import type { SidebarProps } from '@/components/ui/sidebar'

import {
  AudioWaveform,
  BookOpen,
  Bot,
  Command,
  Frame,
  GalleryVerticalEnd,
  LayoutDashboard,
  Map,
  PieChart,
  Settings2,
  SquareTerminal,
  Users,
  UserRound,
  Database,
} from "lucide-vue-next"
import NavMain from '@/components/NavMain.vue'
import NavProjects from '@/components/NavProjects.vue'
import NavUser from '@/components/NavUser.vue'
import TeamSwitcher from '@/components/TeamSwitcher.vue'

import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarRail,
} from '@/components/ui/sidebar'

const props = withDefaults(defineProps<SidebarProps>(), {
  collapsible: "icon",
})

const auth = useAuth()

// Computed user data from auth state
const userData = computed(() => {
  if (!auth.user.value) {
    return {
      name: "Guest",
      email: "",
      avatar: "",
    }
  }

  const initials = auth.user.value.name
    .split(' ')
    .map((n: string) => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)

  return {
    name: auth.user.value.name,
    email: auth.user.value.email,
    avatar: `https://ui-avatars.com/api/?name=${encodeURIComponent(auth.user.value.name)}&background=random`,
    initials,
  }
})

// This is sample data for teams and nav items.
const data = {
  teams: [
    {
      name: "Starter",
      logo: GalleryVerticalEnd,
      plan: "Template"
    }
  ],
  navMain: [
    {
      title: "Dashboard",
      url: "#",
      icon: LayoutDashboard,
      items: [
        {
          title: "Home",
          url: "/",
        }
      ],
    },
    {
      title: "Patients",
      url: "#",
      icon: UserRound,
      items: [
        {
          title: "Patient List",
          url: "/patients",
        }
      ],
    },
    {
      title: "Settings",
      url: "#",
      icon: Settings2,
      items: [
        {
          title: "Users",
          url: "/settings/users",
        },
        {
          title: "Roles",
          url: "/settings/roles",
        },
        {
          title: "Permissions",
          url: "/settings/permissions",
        },
        {
          title: "Activity Logs",
          url: "/settings/activity",
        },
        {
          title: "Reference Data",
          url: "/settings/references",
        },
        {
          title: "Code Generators",
          url: "/settings/code-generators",
        }
      ],
    },
  ],
  projects: [
    {
      name: "Starter",
      url: "#",
      icon: Frame,
    },
    {
      name: "Pulse",
      url: "#",
      icon: PieChart,
    },
    {
      name: "Music",
      url: "#",
      icon: Map,
    },
  ],
}
</script>

<template>
  <Sidebar v-bind="props">
    <SidebarHeader>
      <TeamSwitcher :teams="data.teams" />
    </SidebarHeader>
    <SidebarContent>
      <NavMain :items="data.navMain" />
    </SidebarContent>
    <SidebarFooter>
      <NavUser :user="userData" />
    </SidebarFooter>
    <SidebarRail />
  </Sidebar>
</template>
