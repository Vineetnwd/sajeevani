import React from 'react';
import { Tabs } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useColorScheme } from 'react-native';

export default function StockistLayout() {
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';

  return (
    <Tabs screenOptions={{ 
      headerShown: false,
      tabBarActiveTintColor: isDark ? '#10B981' : '#059669',
      tabBarInactiveTintColor: isDark ? '#6B7280' : '#9CA3AF',
      tabBarStyle: {
        backgroundColor: isDark ? '#1F2937' : '#FFFFFF',
        borderTopWidth: 1,
        borderTopColor: isDark ? '#374151' : '#F3F4F6',
        elevation: 0,
        height: 60,
        paddingBottom: 8,
      },
      tabBarLabelStyle: {
        fontSize: 12,
        fontWeight: '600',
      }
    }}>
      <Tabs.Screen 
        name="home" 
        options={{ 
          title: 'Orders',
          tabBarIcon: ({ color, size }) => (
            <Ionicons name="list-outline" size={size} color={color} />
          )
        }} 
      />
      <Tabs.Screen 
        name="inventory" 
        options={{ 
          title: 'Inventory',
          tabBarIcon: ({ color, size }) => (
            <Ionicons name="cube-outline" size={size} color={color} />
          )
        }} 
      />
    </Tabs>
  );
}
