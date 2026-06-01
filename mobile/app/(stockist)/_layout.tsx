import React from 'react';
import { Tabs } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';

export default function StockistLayout() {
  return (
    <Tabs screenOptions={{ 
      headerShown: false,
      tabBarActiveTintColor: '#059669',
      tabBarInactiveTintColor: '#9CA3AF',
      tabBarStyle: {
        borderTopWidth: 1,
        borderTopColor: '#F3F4F6',
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
